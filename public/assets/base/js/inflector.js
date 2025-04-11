/**
 * Singular
 *
 * Takes a plural word and makes it singular
 *
 * @param	string	str	Input string
 * @return	string
 */
function singular(str) {
    let result = String(str);

    // 명사 변화 규칙 정의
    const singularRules = [
        [/(matr)ices$/, '$1ix'],
        [/(vert|ind)ices$/, '$1ex'],
        [/^(ox)en/, '$1'],
        [/(alias)es$/, '$1'],
        [/([octop|vir])i$/, '$1us'],
        [/(cris|ax|test)es$/, '$1is'],
        [/(shoe)s$/, '$1'],
        [/(o)es$/, '$1'],
        [/(bus|campus)es$/, '$1'],
        [/([m|l])ice$/, '$1ouse'],
        [/(x|ch|ss|sh)es$/, '$1'],
        [/(m)ovies$/, '$1ovie'],
        [/(s)eries$/, '$1eries'],
        [/([^aeiouy]|qu)ies$/, '$1y'],
        [/([lr])ves$/, '$1f'],
        [/(tive)s$/, '$1'],
        [/(hive)s$/, '$1'],
        [/([^f])ves$/, '$1fe'],
        [/(^analy)ses$/, '$1sis'],
        [/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/, '$1sis'],
        [/([ti])a$/, '$1um'],
        [/(p)eople$/, '$1erson'],
        [/(m)en$/, '$1an'],
        [/(s)tatuses$/, '$1tatus'],
        [/(c)hildren$/, '$1hild'],
        [/(n)ews$/, '$1ews'],
        [/([^us])s$/, '$1']
    ];

    // 각 규칙에 따라 일치하는 경우 변환 수행
    for (const [rule, replacement] of singularRules) {
        if (rule.test(result)) {
            result = result.replace(rule, replacement);
            break;
        }
    }

    return result;
}

/**
 * Plural
 *
 * Takes a singular word and makes it plural
 *
 * @param	string	str	Input string
 * @return	string
 */
function plural(str) {
    let result = String(str);

    const plural_rules = [
        { rule: /(quiz)$/, replacement: '$1zes' },            // quizzes
        { rule: /^(ox)$/, replacement: '$1en' },              // ox -> oxen
        { rule: /([m|l])ouse$/, replacement: '$1ice' },       // mouse -> mice
        { rule: /(matr|vert|ind)(ix|ex)$/, replacement: '$1ices' }, // matrix -> matrices
        { rule: /(x|ch|ss|sh)$/, replacement: '$1es' },       // search -> searches
        { rule: /([^aeiouy]|qu)y$/, replacement: '$1ies' },   // query -> queries
        { rule: /(hive)$/, replacement: '$1s' },              // hive -> hives
        { rule: /(?:([^f])fe|([lr])f)$/, replacement: '$1$2ves' }, // wife -> wives
        { rule: /sis$/, replacement: 'ses' },                 // basis -> bases
        { rule: /([ti])um$/, replacement: '$1a' },            // medium -> media
        { rule: /(p)erson$/, replacement: '$1eople' },        // person -> people
        { rule: /(m)an$/, replacement: '$1en' },              // man -> men
        { rule: /(c)hild$/, replacement: '$1hildren' },       // child -> children
        { rule: /(buffal|tomat)o$/, replacement: '$1oes' },   // buffalo -> buffaloes
        { rule: /(bu|campu)s$/, replacement: '$1ses' },       // bus -> buses
        { rule: /(alias|status|virus)$/, replacement: '$1es' }, // alias -> aliases
        { rule: /(octop)us$/, replacement: '$1i' },           // octopus -> octopi
        { rule: /(ax|cris|test)is$/, replacement: '$1es' },   // axis -> axes
        { rule: /s$/, replacement: 's' },                     // no change
        { rule: /$/, replacement: 's' }                       // default to adding 's'
    ];

    for (const { rule, replacement } of plural_rules) {
        if (rule.test(result)) {
            result = result.replace(rule, replacement);
            break;
        }
    }

    return result;
}

function camelize(str) {
    return str.replace(/_([a-z]?)/g, (m, g) => g.toUpperCase());
}

/**
 * Camelize
 *
 * Takes multiple words separated by spaces or underscores and camelizes them
 *
 * @param	string	str	Input string
 * @return	string
 */
function uncamelize(str) {
    return str.replace(/[A-Z]/g, letter => `_${letter.toLowerCase()}`);
}

/**
 * Underscore
 *
 * Takes multiple words separated by spaces and underscores them
 *
 * @param	string	str	Input string
 * @return	string
 */
function underscore(str) {
    return str.replace(/[\s]+/g, letter => `_${letter.toLowerCase()}`);
}

/**
 * Humanize
 *
 * Takes multiple words separated by the separator and changes them to spaces
 *
 * @param	string	str		Input string
 * @param 	string	separator	Input separator
 * @return	string
 */
function humanize(str, separator = '_') {
    // 문자열을 소문자로 변환
    str = str.trim().toLowerCase();

    // 구분자(seperator)를 공백으로 대체
    const regex = new RegExp(`[${separator}]+`, 'g');
    str = str.replace(regex, ' ');

    // 각 단어의 첫 글자를 대문자로 변환
    return str.replace(/\b\w/g, (char) => char.toUpperCase());
}

/**
 * Checks if the given word has a plural version.
 *
 * @param	string	str	Word to check
 * @return	bool
 */
function isCountable(str) {
    return ![
        'equipment', 'information', 'rice', 'money',
        'species', 'series', 'fish', 'meta'
    ].includes(str.toLowerCase());
}

/**
 * Capitalize
 *
 * Let capitalize first letter of word
 *
 * @param	string	str	Input string
 * @return	string
 */
function capitalize(str) {
    return str.charAt(0).toUpperCase() + str.slice(1)
}

/**
 * Pascalize
 *
 * Takes multiple words separated by spaces or underscores and pascalize them
 *
 * @param	string	str	Input string
 * @return	string
 */
function pascalize(str) {
    return capitalize(camelize(str))
}