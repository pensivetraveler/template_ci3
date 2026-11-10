// 테마 vendor 스크립트는 webpack 개발 모드(eval devtool)로 빌드되어 모듈마다 eval("...") 로 싸여 있다.
// CSP 에서 unsafe-eval 없이 돌도록 eval 을 벗겨 같은 코드를 그대로 둔다. 새 vendor 파일을 넣으면 그 파일에 실행한다.
//   node scripts/unwrap-webpack-eval.mjs public/assets/builder/vendor/libs/foo/foo.js
import { readFileSync, writeFileSync } from 'node:fs';
import { parse } from 'acorn';

function evalArgument(node) {
    if (node.type !== 'ExpressionStatement' || node.expression.type !== 'CallExpression') return null;
    const call = node.expression;
    if (call.callee.type !== 'Identifier' || call.callee.name !== 'eval' || call.arguments.length !== 1) return null;
    let arg = call.arguments[0];
    // eval(__webpack_require__.ts("..."))
    if (arg.type === 'CallExpression' && arg.arguments.length === 1 && arg.callee.type === 'MemberExpression' && arg.callee.property.name === 'ts') {
        arg = arg.arguments[0];
    }
    return arg.type === 'Literal' && typeof arg.value === 'string' ? arg : null;
}

function walk(node, visit) {
    if (!node || typeof node.type !== 'string') return;
    visit(node);
    for (const value of Object.values(node)) {
        if (Array.isArray(value)) value.forEach((child) => walk(child, visit));
        else if (value && typeof value.type === 'string') walk(value, visit);
    }
}

for (const file of process.argv.slice(2)) {
    const source = readFileSync(file, 'utf8');
    const options = { ecmaVersion: 'latest', sourceType: 'script', allowHashBang: true, allowReturnOutsideFunction: true };
    const edits = [];
    walk(parse(source, options), (node) => {
        const arg = evalArgument(node);
        if (!arg) return;
        const code = arg.value.replace(/\/\/# sourceURL=.*$/m, '').trimEnd();
        parse(code, { ...options, allowAwaitOutsideFunction: false });
        // 블록으로 싸서 let·const 가 모듈 함수 인자와 부딪히지 않게 한다 (eval 안에서와 같은 범위). 끝 줄 주석이 } 를 먹지 않게 줄을 바꾼다
        edits.push([node.start, node.end, `{\n${code}\n}`]);
    });
    if (!edits.length) {
        console.log(`0 ${file}`);
        continue;
    }
    let output = source;
    for (const [start, end, text] of edits.reverse()) output = output.slice(0, start) + text + output.slice(end);
    output = output.replace(/^\/\*\n \* ATTENTION: The "eval" devtool[\s\S]*?\*\/\n/m, '');
    parse(output, options);
    writeFileSync(file, output);
    console.log(`${edits.length} ${file}`);
}
