import { globSync, readFileSync } from 'node:fs';
import js from '@eslint/js';
import globals from 'globals';

const ownFiles = ['public/assets/builder/js/**/*.js', 'public/assets/admin/js/**/*.js'];

// 화면 스크립트는 모듈 없이 전역을 함께 쓴다. 파일 맨 위에 선언한 이름을 전역으로 알려 주면 no-undef 가 오타만 잡는다
const sharedGlobals = {};
for (const file of globSync([...ownFiles, 'public/assets/base/js/*.js'], { exclude: (name) => name.endsWith('.bak.js') })) {
    for (const [, name] of readFileSync(file, 'utf8').matchAll(/^(?:async\s+)?(?:function\*?|class|let|const|var)\s+([\w$]+)/gm)) {
        sharedGlobals[name] = 'writable';
    }
    for (const [, line] of readFileSync(file, 'utf8').matchAll(/^(?:let|var)\s+([\w$]+(?:\s*,\s*[\w$]+)+)\s*;/gm)) {
        for (const name of line.split(',')) sharedGlobals[name.trim()] = 'writable';
    }
}

export default [
    {
        // app-page-helpers 는 어디서도 불러오지 않는 vendor/js/helpers.js 사본, app-calendar·app-charts 는 테마 예제다
        ignores: ['**/*.bak.js', '**/vendor/**', '**/libs/**', 'public/assets/base/**', '**/app-page-helpers.js', '**/app-calendar*.js', '**/app-charts.js'],
    },
    js.configs.recommended,
    {
        files: ownFiles,
        languageOptions: {
            ecmaVersion: 2022,
            sourceType: 'script',
            globals: {
                ...globals.browser,
                ...globals.jquery,
                ...sharedGlobals,
                // public/assets/builder/vendor 에서 불러오는 라이브러리, PHP 가 넣는 변수
                common: 'readonly',
                FormValidation: 'readonly',
                bootstrap: 'readonly',
                Waves: 'readonly',
                Cleave: 'readonly',
                Dropzone: 'readonly',
                Swal: 'readonly',
                i18next: 'readonly',
                autosize: 'readonly',
                XLSX: 'readonly',
                Quill: 'readonly',
                Sortable: 'readonly',
                Tagify: 'readonly',
                Coloris: 'readonly',
                ClipboardJS: 'readonly',
                PerfectScrollbar: 'readonly',
                TemplateCustomizer: 'readonly',
                MegaDropdown: 'readonly',
                Menu: 'readonly',
                monthSelectPlugin: 'readonly',
                daum: 'readonly',
                i18NextHttpBackend: 'readonly',
                moment: 'readonly',
                appName: 'readonly',
                excelHeaders: 'readonly',
                assetsPath: 'writable',
                templateName: 'writable',
                rtlSupport: 'writable',
                config: 'writable',
                isRtl: 'writable',
                isDarkStyle: 'writable',
            },
        },
        rules: {
            // 파일 맨 위 이름은 다른 파일에서 쓰고, 콜백 인자는 형태를 맞추려고 남긴다
            'no-unused-vars': ['warn', { vars: 'local', args: 'none', caughtErrors: 'none' }],
            'no-redeclare': ['error', { builtinGlobals: false }],
            'no-empty': ['error', { allowEmptyCatch: true }],
        },
    },
];
