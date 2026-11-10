/**
 * The JSONFormatter helper module. This contains two major functions, jsonToHTML and errorPage,
 * each of which returns an HTML document.
 */
/** Convert a whole JSON value / JSONP response into a formatted HTML document */
function jsonToHTML(json, uri) {
    return toHTML(jsonToHTMLBody(json), uri);
}
/** Convert a whole JSON value / JSONP response into an HTML body, without title and scripts */
function jsonToHTMLBody(json) {
    return `<div id="json">${valueToHTML(json, "<root>", 0)}</div>`;
}
/** Produce an error document for when parsing fails. */
function errorPage(error, data, uri) {
    return toHTML(errorPageBody(error, data), uri + " - Error");
}
/** Produce an error content for when parsing fails. */
function errorPageBody(error, data) {
    // Escape unicode nulls
    data = data.replace("\u0000", "\uFFFD");
    const errorInfo = massageError(error);
    let output = `<div id="error">${chrome.i18n.getMessage("errorParsing")}`;
    if (errorInfo.message) {
        output += `<div class="errormessage">${errorInfo.message}</div>`;
    }
    output += `</div><div id="json">${highlightError(data, errorInfo.line, errorInfo.column)}</div>`;
    return output;
}
/**
 * Encode a string to be used in HTML
 */
function htmlEncode(t) {
    return t !== undefined && t !== null
        ? t
            .toString()
            .replace(/&/g, "&amp;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&apos;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
        : "";
}
/**
 * Completely escape a json string
 */
function jsString(s) {
    // Slice off the surrounding quotes
    s = JSON.stringify(s).slice(1, -1);
    return htmlEncode(s);
}
/**
 * Is this a valid "bare" property name (e.g. a JS identifier name or a numeric
 * literal)? See https://mathiasbynens.be/notes/javascript-properties, though we
 * only support a subset of valid identifier names (no weird Unicode stuff).
 */
function isBareProp(prop) {
    return /^([0-9]+|[A-Za-z_$][A-Za-z0-9_$]*)$/.test(prop);
}
/**
 * Surround value with a span, including the given className
 */
function decorateWithSpan(value, className) {
    return `<span class="${className}">${htmlEncode(value)}</span>`;
}
// Convert a basic JSON datatype (number, string, boolean, null, object, array) into an HTML fragment.
function valueToHTML(value, path, indent) {
    if (value === null) {
        return decorateWithSpan("null", "null");
    }
    else if (Array.isArray(value)) {
        return arrayToHTML(value, path, indent);
    }
    // eslint-disable-next-line @typescript-eslint/switch-exhaustiveness-check
    switch (typeof value) {
        case "object":
            return objectToHTML(value, path, indent);
        case "number":
            return decorateWithSpan(value, "num");
        case "boolean":
            return decorateWithSpan(value, "bool");
        case "string":
            if (value.charCodeAt(0) === 8203 /* zero-width space */ && !isNaN(Number(value.slice(1)))) {
                return decorateWithSpan(Number(value.slice(1)), "num");
            }
            else if (/^(http|https|file):\/\/[^\s]+$/i.test(value)) {
                return `<a href="${htmlEncode(value)}"><span class="q">&quot;</span>${jsString(value)}<span class="q">&quot;</span></a>`;
            }
            else {
                return `<span class="string">&quot;${jsString(value)}&quot;</span>`;
            }
        default:
            return "";
    }
}
// Convert an array into an HTML fragment
function arrayToHTML(json, path, indent) {
    if (json.length === 0) {
        return "[ ]";
    }
    let output = "";
    for (let i = 0; i < json.length; i++) {
        const subPath = `${path}[${i}]`;
        output += "<li>" + addIndent(indent + 1) + valueToHTML(json[i], subPath, indent + 1);
        if (i < json.length - 1) {
            output += ",";
        }
        output += "</li>";
    }
    return ((json.length === 0 ? "" : '<span class="collapser"></span>') +
        `[<ul class="array collapsible">${output}</ul>${addIndent(indent)}]`);
}
function addIndent(indent) {
    return `<span class="spacer">${"&nbsp;&nbsp;".repeat(indent)}</span>`;
}
// Convert a JSON object to an HTML fragment
function objectToHTML(json, path, indent) {
    let numProps = Object.keys(json).length;
    if (numProps === 0) {
        return "{ }";
    }
    let output = "";
    for (const prop in json) {
        let subPath = "";
        let escapedProp = JSON.stringify(prop).slice(1, -1);
        const bare = isBareProp(prop);
        if (bare) {
            subPath = `${path}.${escapedProp}`;
        }
        else {
            escapedProp = `"${escapedProp}"`;
        }
        output += `<li>${addIndent(indent + 1)}<span class="prop${bare ? "" : " quoted"}" title="${htmlEncode(subPath)}"><span class="q">&quot;</span>${jsString(prop)}<span class="q">&quot;</span></span>: ${valueToHTML(json[prop], subPath, indent + 1)}`;
        if (numProps > 1) {
            output += ",";
        }
        output += "</li>";
        numProps--;
    }
    return `<span class="collapser"></span>{<ul class="obj collapsible">${output}</ul>${addIndent(indent)}}`;
}
// Clean up a JSON parsing error message
function massageError(error) {
    if (!error.message) {
        return error;
    }
    const message = error.message.replace(/^JSON.parse: /, "").replace(/of the JSON data/, "");
    const parts = /line (\d+) column (\d+)/.exec(message);
    if (!parts || parts.length !== 3) {
        return error;
    }
    return {
        message: htmlEncode(message),
        line: Number(parts[1]),
        column: Number(parts[2]),
    };
}
function highlightError(data, lineNum, columnNum) {
    if (!lineNum || !columnNum) {
        return htmlEncode(data);
    }
    const lines = data.match(/^.*((\r\n|\n|\r)|$)/gm);
    let output = "";
    for (let i = 0; i < lines.length; i++) {
        const line = lines[i];
        if (i === lineNum - 1) {
            output += '<span class="errorline">';
            output += `${htmlEncode(line.substring(0, columnNum - 1))}<span class="errorcolumn">${htmlEncode(line[columnNum - 1])}</span>${htmlEncode(line.substring(columnNum))}`;
            output += "</span>";
        }
        else {
            output += htmlEncode(line);
        }
    }
    return output;
}
// Wrap the HTML fragment in a full document. Used by jsonToHTML and errorPage.
function toHTML(content, title) {
    return `<!DOCTYPE html>
<html><head><title>${htmlEncode(title)}</title>
<meta charset="utf-8">
<meta name="color-scheme" content="light dark">
<link rel="stylesheet" type="text/css" href="${chrome.runtime.getURL("viewer.css")}">
</head><body>
${content}
</body></html>`;
}

/**
 * Add event handlers that allow for collapsing and expanding JSON structures, with the mouse or keyboard.
 */
function installCollapseEventListeners() {
    // Click handler for collapsing and expanding objects and arrays
    function collapse(evt) {
        let collapser = evt.target;
        while (collapser && !collapser.classList?.contains("collapser")) {
            collapser = collapser.nextSibling;
        }
        if (!collapser?.classList?.contains("collapser")) {
            return;
        }
        evt.stopPropagation();
        collapser.classList.toggle("collapsed");
        let collapsible = collapser;
        while (collapsible && !collapsible.classList?.contains("collapsible")) {
            collapsible = collapsible.nextSibling;
        }
        collapsible.classList.toggle("collapsed");
    }
    /*
     * Collapses the whole json using keyboard
     * TODO: Add a navigator support for each of the elements
     */
    function collapseAll(evt) {
        let inputList;
        let i;
        // Ignore anything paired with a modifier key. See https://github.com/bhollis/jsonview/issues/69
        if (evt.ctrlKey || evt.shiftKey || evt.altKey || evt.metaKey) {
            return;
        }
        if (evt.key === "ArrowLeft") {
            // Collapses the json on left arrow key up
            inputList = document.querySelectorAll(".collapsible, .collapser");
            for (i = 0; i < inputList.length; i++) {
                if (inputList[i].parentNode.id !== "json") {
                    inputList[i].classList.add("collapsed");
                }
            }
            evt.preventDefault();
        }
        else if (evt.key === "ArrowRight") {
            // Expands the json on right arrow key up
            inputList = document.querySelectorAll(".collapsed");
            for (i = 0; i < inputList.length; i++) {
                inputList[i].classList.remove("collapsed");
            }
            evt.preventDefault();
        }
    }
    // collapse with event delegation
    document.addEventListener("click", collapse, false);
    document.addEventListener("keyup", collapseAll, false);
}

/*
  *  Takes a JSON string and replaces number values with strings with a leading \u200B.
  *  Prior to this, it doubles any pre-existing \u200B characters. This Unicode value is
  *  a zero-width space, so doubling it won't affect the HTML view.
  *
  *  This addresses JSONView issue 21 (https://github.com/bhollis/jsonview/issues/21),
  *  where numbers larger than Number.MAX_SAFE_INTEGER get rounded to the nearest value
  *  that can fit in the mantissa. Instead we will string encode those numbers, and rely
  *  on JSONFormatter to detect the leading zero-width space, check the remainder of the
  *  string with !isNaN() for number-ness, and render it with number styling, sans-quotes.
  */
function safeStringEncodeNums(jsonString) {
    const viewString = jsonString.replace(/\u200B/g, "\u200B\u200B");
    // This has some memory of what its last state was
    let wasInQuotes = false;
    function isInsideQuotes(str) {
        let inQuotes = false;
        for (let i = 0; i < str.length; i++) {
            if (str[i] === '"') {
                let escaped = false;
                for (let lookback = i - 1; lookback >= 0; lookback--) {
                    if (str[lookback] === '\\') {
                        escaped = !escaped;
                    }
                    else {
                        break;
                    }
                }
                if (!escaped) {
                    inQuotes = !inQuotes;
                }
            }
        }
        if (wasInQuotes) {
            inQuotes = !inQuotes;
        }
        wasInQuotes = inQuotes;
        return inQuotes;
    }
    let startIndex = 0;
    function replaceNumbers(match, index) {
        // Substring should be copy-on-write, and thus cheap
        const lookback = viewString.substring(startIndex, index);
        const insideQuotes = isInsideQuotes(lookback);
        startIndex = index + match.length;
        return insideQuotes ? match : `"\u200B${match}"`;
    }
    // JSON legal number matcher, Andrew Cheong, http://stackoverflow.com/questions/13340717
    const numberFinder = /-?(?:0|[1-9]\d*)(?:\.\d+)?(?:[eE][+-]?\d+)?/g;
    return viewString.replace(numberFinder, replaceNumbers);
}

/**
 * This script runs on every page. It communicates with the background script
 * to help decide whether to treat the contents of the page as JSON.
 */
chrome.runtime.sendMessage("jsonview-is-json", (response) => {
    if (!response) {
        return;
    }
    // At least in chrome, the JSON is wrapped in a pre tag.
    const jsonElems = document.getElementsByTagName("pre");
    let content = null;
    if (jsonElems.length >= 1) {
        content = jsonElems[0].textContent;
    }
    else {
        // Sometimes there's no pre? I'm not sure why this would happen
        content = (document.body.firstChild ?? document.body).textContent;
    }
    let outputDoc = "";
    let jsonObj = null;
    if (content === null) {
        outputDoc = errorPage(new Error("No content"), "", document.URL);
    }
    else {
        try {
            // eslint-disable-next-line @typescript-eslint/no-unsafe-assignment
            jsonObj = JSON.parse(safeStringEncodeNums(content));
            outputDoc = jsonToHTML(jsonObj, document.URL);
        }
        catch (e) {
            outputDoc = errorPage(e instanceof Error ? e : typeof e === "string" ? new Error(e) : new Error("Unknown error"), content, document.URL);
        }
    }
    document.documentElement.innerHTML = outputDoc;
    installCollapseEventListeners();
});
