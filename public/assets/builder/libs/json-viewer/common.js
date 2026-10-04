function escape_html(str)
{
    return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

function json_to_colored_html(obj, indent)
{
    indent = indent || 0;
    var pad = ' '.repeat(indent);
    var html = '';

    if (obj === null) {
        return '<span class="json-value-null">null</span>';
    }

    if (typeof obj === 'string') {
        return '<span class="json-value-string">"' + escape_html(obj) + '"</span>';
    }

    if (typeof obj === 'number') {
        return '<span class="json-value-number">' + obj + '</span>';
    }

    if (typeof obj === 'boolean') {
        return '<span class="json-value-boolean">' + obj + '</span>';
    }

    if (Array.isArray(obj)) {
        if (obj.length === 0) {
            return '[]';
        }

        html += '[\n';

        for (var i = 0; i < obj.length; i++) {
            html += ' '.repeat(indent + 2);
            html += json_to_colored_html(obj[i], indent + 2);

            if (i < obj.length - 1) {
                html += ',';
            }

            html += '\n';
        }

        html += pad + ']';
        return html;
    }

    if (typeof obj === 'object') {
        var keys = Object.keys(obj);

        if (keys.length === 0) {
            return '{}';
        }

        html += '{\n';

        for (var j = 0; j < keys.length; j++) {
            var key = keys[j];
            html += ' '.repeat(indent + 2);
            html += '<span class="json-key">"' + escape_html(key) + '"</span>: ';
            html += json_to_colored_html(obj[key], indent + 2);

            if (j < keys.length - 1) {
                html += ',';
            }

            html += '\n';
        }

        html += pad + '}';
        return html;
    }

    return '';
}
