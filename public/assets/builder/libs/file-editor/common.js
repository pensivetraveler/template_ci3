/**
 * 현재 선택된 파일 상태
 */
let currentFilePath = '';
let currentFileType = '';
let API_BASE = '/module/file';

function fetchData(url, options = {}, method = 'GET') {
    options = Object.assign({
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        }
    }, options);

    return fetch(url, options)
        .then(function (res) {
            return res.json();
        })
        .then(function (res) {
            if (!res.success) {
                alert(res.message);
                return;
            }

            return res.data;
        });
}

/**
 * 파일 트리 로드
 */
function loadTree(dir) {
    fetchData(API_BASE + '/tree?dir=' + encodeURIComponent(dir))
        .then(function (data) {
            const treeElement = document.getElementById('fileTree');
            treeElement.innerHTML = '';

            renderTree(data.tree, treeElement);
        })
        .catch(function (err) {
            console.error(err);
            alert('파일 목록을 불러오지 못했습니다.');
        });
}

/**
 * 파일 트리 렌더링
 */
function renderTree(items, parentElement) {
    items.forEach(function (item) {
        const li = document.createElement('li');

        if (item.type === 'directory') {
            const dirDiv = document.createElement('div');

            dirDiv.className = 'file-tree-item directory';
            dirDiv.innerHTML = ''
                + '<span class="file-tree-icon">📁</span>'
                + '<span class="file-tree-name">' + escapeHtml(item.name) + '</span>'
                + '<span class="file-tree-toggle">▶</span>';

            const childUl = document.createElement('ul');
            childUl.className = 'file-tree-children';

            renderTree(item.children, childUl);

            dirDiv.addEventListener('click', function (e) {
                e.stopPropagation();

                const isOpen = childUl.classList.contains('open');

                if (isOpen) {
                    childUl.classList.remove('open');
                    dirDiv.classList.remove('open');
                    dirDiv.querySelector('.file-tree-icon').innerText = '📁';
                } else {
                    childUl.classList.add('open');
                    dirDiv.classList.add('open');
                    dirDiv.querySelector('.file-tree-icon').innerText = '📂';
                }
            });

            li.appendChild(dirDiv);
            li.appendChild(childUl);
        } else {
            const fileDiv = document.createElement('div');

            fileDiv.className = 'file-tree-item';
            fileDiv.setAttribute('data-path', item.path);

            fileDiv.innerHTML = ''
                + '<span class="file-tree-icon">' + getFileIcon(item) + '</span>'
                + '<span class="file-tree-name" title="' + escapeHtml(item.path) + '">' + escapeHtml(item.name) + '</span>'
                + '<span class="badge rounded-pill bg-light text-secondary border file-tree-ext">' + escapeHtml(item.extension || '') + '</span>';

            fileDiv.addEventListener('click', function (e) {
                e.stopPropagation();

                setActiveFileTreeItem(item.path);

                if (item.image) {
                    loadImageFile(item.path);
                    return;
                }

                if (item.editable) {
                    loadEditableFile(item.path);
                    return;
                }

                alert('편집할 수 없는 파일입니다.');
            });

            li.appendChild(fileDiv);
        }

        parentElement.appendChild(li);
    });
}

/**
 * 선택 파일 active 표시
 */
function setActiveFileTreeItem(path) {
    document.querySelectorAll('.file-tree-item.active').forEach(function (el) {
        el.classList.remove('active');
    });

    const target = document.querySelector('.file-tree-item[data-path="' + cssEscape(path) + '"]');

    if (target) {
        target.classList.add('active');
    }
}

/**
 * 파일 아이콘
 */
function getFileIcon(item) {
    if (item.image) {
        return '🖼️';
    }

    switch (item.extension) {
        case 'json':
            return '🧩';
        case 'css':
            return '🎨';
        case 'js':
            return '📜';
        case 'html':
            return '🌐';
        default:
            return '📄';
    }
}

/**
 * 텍스트 파일 로드
 */
function loadEditableFile(path) {
    fetchData(API_BASE + '/read?path=' + encodeURIComponent(path))
        .then(function (data) {
            currentFilePath = data.path;
            currentFileType = 'text';

            document.getElementById('currentFileLabel').innerText = currentFilePath;

            showTextEditor();

            initFileEditor('editor').then(function (editor) {
                const lang = getLanguageByExtension(data.extension);

                monaco.editor.setModelLanguage(editor.getModel(), lang);
                editor.setValue(data.content);

                setTimeout(function () {
                    editor.layout();
                }, 0);
            });
        })
        .catch(function (err) {
            console.error(err);
            alert('파일을 불러오지 못했습니다.');
        });
}

/**
 * 이미지 파일 로드
 */
function loadImageFile(path) {
    fetchData(API_BASE + '/image_info?path=' + encodeURIComponent(path))
        .then(function (data) {
            currentFilePath = data.path;
            currentFileType = 'image';

            document.getElementById('currentFileLabel').innerText = currentFilePath;

            showImageEditor();

            document.getElementById('imagePreview').src = data.url;
        })
        .catch(function (err) {
            console.error(err);
            alert('이미지를 불러오지 못했습니다.');
        });
}

/**
 * 현재 파일 저장
 */
function saveCurrentFile() {
    if (!currentFilePath) {
        alert('저장할 파일을 선택하세요.');
        return;
    }

    if (currentFileType !== 'text') {
        alert('이미지는 이미지 교체 기능을 사용하세요.');
        return;
    }

    const editor = window.__fileEditorInstance;

    if (!editor) {
        alert('에디터가 초기화되지 않았습니다.');
        return;
    }

    const content = editor.getValue();

    fetchData(API_BASE + '/save', {
        method: 'POST',
        body: JSON.stringify({
            path: currentFilePath,
            content: content
        })
    })
        .then(function () {
            alert('저장되었습니다.');
        })
        .catch(function (err) {
            console.error(err);
            alert('파일 저장 중 오류가 발생했습니다.');
        });
}

/**
 * 현재 이미지 교체
 */
function uploadCurrentImage() {
    if (!currentFilePath) {
        alert('이미지 파일을 선택하세요.');
        return;
    }

    if (currentFileType !== 'image') {
        alert('현재 선택된 파일은 이미지가 아닙니다.');
        return;
    }

    const input = document.getElementById('imageInput');

    if (!input.files || !input.files[0]) {
        alert('업로드할 이미지를 선택하세요.');
        return;
    }

    const formData = new FormData();

    formData.append('path', currentFilePath);
    formData.append('image', input.files[0]);

    fetchData(API_BASE + '/upload_image', {
            method: 'POST',
            body: formData
        })
        .then(function (data) {
            document.getElementById('imagePreview').src = data.url;
            input.value = '';

            alert('이미지가 교체되었습니다.');
        })
        .catch(function (err) {
            console.error(err);
            alert('이미지 업로드 중 오류가 발생했습니다.');
        });
}

/**
 * 화면 전환
 */
function showTextEditor() {
    document.getElementById('editorEmptyState').classList.add('d-none');
    document.getElementById('imageArea').classList.add('d-none');
    document.getElementById('editorWrap').classList.remove('d-none');
}

function showImageEditor() {
    document.getElementById('editorEmptyState').classList.add('d-none');
    document.getElementById('editorWrap').classList.add('d-none');
    document.getElementById('imageArea').classList.remove('d-none');
}

function showEmptyState() {
    document.getElementById('editorWrap').classList.add('d-none');
    document.getElementById('imageArea').classList.add('d-none');
    document.getElementById('editorEmptyState').classList.remove('d-none');
}

function clearEditorSelection() {
    currentFilePath = '';
    currentFileType = '';

    document.getElementById('currentFileLabel').innerText = '파일을 선택하세요.';

    document.querySelectorAll('.file-tree-item.active').forEach(function (el) {
        el.classList.remove('active');
    });

    if (window.__fileEditorInstance) {
        window.__fileEditorInstance.setValue('');
    }

    document.getElementById('imagePreview').src = '';
    document.getElementById('imageInput').value = '';

    showEmptyState();
}

/**
 * 확장자별 Monaco language
 */
function getLanguageByExtension(ext) {
    ext = String(ext).toLowerCase();

    switch (ext) {
        case 'js':
            return 'javascript';
        case 'css':
            return 'css';
        case 'json':
            return 'json';
        case 'html':
            return 'html';
        case 'txt':
            return 'plaintext';
        default:
            return 'plaintext';
    }
}

/**
 * HTML escape
 */
function escapeHtml(str) {
    return String(str)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

/**
 * CSS selector escape fallback
 */
function cssEscape(str) {
    if (window.CSS && typeof window.CSS.escape === 'function') {
        return window.CSS.escape(str);
    }

    return String(str).replace(/["\\]/g, '\\$&');
}

/**
 * Monaco FollowLink 커스텀 Provider 등록
 *
 * HTML/CSS/JS/JSON 내부의 파일 경로를 Ctrl/Cmd + Click 가능한 링크로 만든다.
 */
function registerCustomFileLinkProvider(monaco) {
    if (window.__customFileLinkProviderRegistered) {
        return;
    }

    window.__customFileLinkProviderRegistered = true;

    const targetLanguages = [
        'html',
        'css',
        'javascript',
        'json',
        'plaintext'
    ];

    targetLanguages.forEach(function (languageId) {
        monaco.languages.registerLinkProvider(languageId, {
            provideLinks: function (model, token) {
                const links = [];
                const lineCount = model.getLineCount();

                for (let lineNumber = 1; lineNumber <= lineCount; lineNumber++) {
                    const line = model.getLineContent(lineNumber);

                    collectFileLinksFromLine(line, lineNumber, links);
                }

                return {
                    links: links
                };
            }
        });
    });
}

/**
 * 한 줄에서 파일 경로 패턴을 찾아 Monaco 링크로 등록
 */
function collectFileLinksFromLine(line, lineNumber, links) {
    const patterns = [
        /**
         * HTML
         * src="images/logo.png"
         * href="./style.css"
         */
        /(?:src|href)=["']([^"']+)["']/gi,

        /**
         * CSS
         * url(images/bg.jpg)
         * url('../images/bg.jpg')
         */
        /url\(\s*["']?([^"')]+)["']?\s*\)/gi,

        /**
         * JS
         * import "./app.js"
         * from "../module/a.js"
         */
        /import\s+["']([^"']+)["']/gi,
        /from\s+["']([^"']+)["']/gi,

        /**
         * JSON or JS object
         * "image": "images/a.png"
         * "css": "./style.css"
         * "script": "script.js"
         */
        /["'](?:path|file|src|href|url|image|img|css|style|script|js)["']\s*:\s*["']([^"']+)["']/gi
    ];

    patterns.forEach(function (regex) {
        let match;

        while ((match = regex.exec(line)) !== null) {
            const rawPath = match[1];

            if (shouldIgnoreEditorFileLink(rawPath)) {
                continue;
            }

            const startColumn = match.index + match[0].indexOf(rawPath) + 1;
            const endColumn = startColumn + rawPath.length;

            const resolvedPath = resolveEditorRelativePath(currentFilePath, rawPath);

            links.push({
                range: new window.monaco.Range(
                    lineNumber,
                    startColumn,
                    lineNumber,
                    endColumn
                ),
                url: window.monaco.Uri.parse('file-editor://' + encodeURIComponent(resolvedPath)),
                tooltip: 'Open file: ' + resolvedPath
            });
        }
    });
}

/**
 * Monaco FollowLink 실행 시 호출됨
 *
 * file-editor:// 경로면 내부 파일 에디터에서 열고,
 * 일반 http/https URL이면 새 탭으로 연다.
 */
function handleMonacoFollowLink(link, options) {
    if (link.indexOf('file-editor://') === 0) {
        const filePath = decodeURIComponent(link.replace('file-editor://', ''));

        openEditorFileByPath(filePath);

        return true;
    }

    if (/^https?:\/\//i.test(link)) {
        window.open(link, '_blank', 'noopener,noreferrer');
        return true;
    }

    return true;
}

/**
 * 파일트리에서 파일을 클릭한 것과 같은 효과
 */
function openEditorFileByPath(path) {
    const normalizedPath = normalizePath(path);
    const ext = getFileExtension(normalizedPath);

    /**
     * 좌측 파일트리 active 표시
     */
    setActiveFileTreeItem(normalizedPath);

    /**
     * 이미지면 이미지 미리보기
     */
    if (isImageExtensionForEditor(ext)) {
        loadImageFile(normalizedPath);
        return;
    }

    /**
     * 코드/텍스트 파일이면 Monaco Editor에 표시
     */
    if (isEditableExtensionForEditor(ext)) {
        loadEditableFile(normalizedPath);
        return;
    }

    alert('열 수 없는 파일 형식입니다: ' + normalizedPath);
}

/**
 * 현재 파일 기준으로 상대경로 해석
 *
 * 예:
 * currentFilePath = sample1/pages/index.html
 * rawPath = ../images/logo.png
 * result = sample1/images/logo.png
 */
function resolveEditorRelativePath(currentPath, targetPath) {
    targetPath = String(targetPath || '').trim();

    /**
     * query/hash 제거
     * images/logo.png?v=1#abc → images/logo.png
     */
    targetPath = targetPath.split('?')[0].split('#')[0];

    /**
     * /images/a.png 형태는 현재 root 기준으로 처리
     */
    if (targetPath.charAt(0) === '/') {
        return normalizePath(targetPath.replace(/^\/+/, ''));
    }

    if (!currentPath) {
        return normalizePath(targetPath);
    }

    const currentDir = currentPath.split('/').slice(0, -1).join('/');
    const combined = currentDir ? currentDir + '/' + targetPath : targetPath;

    return normalizePath(combined);
}

/**
 * ../, ./ 정리
 */
function normalizePath(path) {
    const parts = String(path || '').replace(/\\/g, '/').split('/');
    const stack = [];

    parts.forEach(function (part) {
        if (!part || part === '.') {
            return;
        }

        if (part === '..') {
            if (stack.length > 0) {
                stack.pop();
            }

            return;
        }

        stack.push(part);
    });

    return stack.join('/');
}

/**
 * 파일 확장자 추출
 */
function getFileExtension(path) {
    const filename = String(path || '').split('/').pop();

    if (filename.indexOf('.') === -1) {
        return '';
    }

    return filename.split('.').pop().toLowerCase();
}

function isEditableExtensionForEditor(ext) {
    return ['json', 'css', 'js', 'html', 'txt'].indexOf(ext) !== -1;
}

function isImageExtensionForEditor(ext) {
    return ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].indexOf(ext) !== -1;
}

/**
 * 내부 파일 링크로 처리하지 않을 값
 */
function shouldIgnoreEditorFileLink(path) {
    path = String(path || '').trim();

    if (!path) {
        return true;
    }

    /**
     * 외부 URL
     */
    if (/^(https?:)?\/\//i.test(path)) {
        return true;
    }

    /**
     * data:image 등
     */
    if (/^data:/i.test(path)) {
        return true;
    }

    /**
     * mailto, tel
     */
    if (/^(mailto|tel):/i.test(path)) {
        return true;
    }

    /**
     * anchor
     */
    if (path.charAt(0) === '#') {
        return true;
    }

    /**
     * 확장자 없는 값은 제외
     */
    const ext = getFileExtension(path);

    if (!ext) {
        return true;
    }

    const allowed = [
        'json',
        'css',
        'js',
        'html',
        'txt',
        'jpg',
        'jpeg',
        'png',
        'gif',
        'webp',
        'svg'
    ];

    return allowed.indexOf(ext) === -1;
}

/**
 * Monaco Editor에서 Ctrl/Cmd + Click을 직접 감지해서
 * 파일트리 클릭과 동일하게 파일을 연다.
 */
function registerEditorFollowLinkClickHandler(editor) {
    if (window.__fileEditorFollowClickRegistered) {
        return;
    }

    window.__fileEditorFollowClickRegistered = true;

    editor.onMouseDown(function (e) {
        const browserEvent = e.event.browserEvent;

        /**
         * Windows/Linux: Ctrl + Click
         * macOS: Cmd + Click
         */
        const isFollowLinkClick = browserEvent.ctrlKey || browserEvent.metaKey;

        if (!isFollowLinkClick) {
            return;
        }

        if (!e.target || !e.target.position) {
            return;
        }

        const model = editor.getModel();

        if (!model) {
            return;
        }

        const position = e.target.position;
        const line = model.getLineContent(position.lineNumber);

        const linkInfo = findEditorFileLinkAtPosition(
            line,
            position.lineNumber,
            position.column
        );

        if (!linkInfo) {
            return;
        }

        browserEvent.preventDefault();
        browserEvent.stopPropagation();

        openEditorFileByPath(linkInfo.resolvedPath);
    });
}

/**
 * 현재 클릭한 위치가 파일 경로 위인지 확인
 */
function findEditorFileLinkAtPosition(line, lineNumber, column) {
    const patterns = [
        /**
         * HTML
         * src="images/logo.png"
         * href="./style.css"
         */
        /(?:src|href)=["']([^"']+)["']/gi,

        /**
         * CSS
         * url(images/bg.jpg)
         * url('../images/bg.jpg')
         */
        /url\(\s*["']?([^"')]+)["']?\s*\)/gi,

        /**
         * JS
         * import "./app.js"
         * from "../module/a.js"
         */
        /import\s+["']([^"']+)["']/gi,
        /from\s+["']([^"']+)["']/gi,

        /**
         * JSON or JS object
         */
        /["'](?:path|file|src|href|url|image|img|css|style|script|js)["']\s*:\s*["']([^"']+)["']/gi
    ];

    for (let i = 0; i < patterns.length; i++) {
        const regex = patterns[i];
        let match;

        regex.lastIndex = 0;

        while ((match = regex.exec(line)) !== null) {
            const rawPath = match[1];

            if (shouldIgnoreEditorFileLink(rawPath)) {
                continue;
            }

            const startColumn = match.index + match[0].indexOf(rawPath) + 1;
            const endColumn = startColumn + rawPath.length;

            /**
             * Monaco column은 1-base이고,
             * 경로 위를 클릭했는지 검사
             */
            if (column >= startColumn && column <= endColumn) {
                const resolvedPath = resolveEditorRelativePath(currentFilePath, rawPath);

                return {
                    rawPath: rawPath,
                    resolvedPath: resolvedPath,
                    lineNumber: lineNumber,
                    startColumn: startColumn,
                    endColumn: endColumn
                };
            }
        }
    }

    return null;
}

/**
 * Monaco Editor 붙여넣기 중복 방지
 *
 * 특정 브라우저 환경에서 Cmd/Ctrl + V 시
 *
 * 1. Monaco 내부 programmatic paste
 * 2. browser native paste
 *
 * 가 연속으로 발생하여 같은 내용이 두 번 입력되는 현상을 방지한다.
 *
 * 우클릭 Paste 등 정상적인 native paste는 그대로 허용한다.
 */
function registerEditorPasteWorkaround(editor) {
    if (!editor) {
        return;
    }

    const editorDom = editor.getDomNode();

    if (!editorDom) {
        return;
    }

    const textarea =
        editorDom.querySelector('textarea.inputarea');

    if (!textarea) {
        return;
    }

    /**
     * 동일 editor에 중복 등록 방지
     */
    if (textarea.__pasteWorkaroundRegistered) {
        return;
    }

    textarea.__pasteWorkaroundRegistered = true;

    let lastProgrammaticPasteAt = 0;
    let resetTimer = null;

    /**
     * Monaco 내부 paste 감지
     */
    const pasteDisposable =
        editor.onDidPaste(function (e) {

            /**
             * clipboardEvent가 없으면
             * Monaco 내부 programmatic paste로 판단
             */
            if (!e.clipboardEvent) {
                lastProgrammaticPasteAt =
                    performance.now();

                /**
                 * stale 상태 방지
                 */
                if (resetTimer) {
                    clearTimeout(resetTimer);
                }

                resetTimer = setTimeout(function () {
                    lastProgrammaticPasteAt = 0;
                    resetTimer = null;
                }, 200);
            }
        });

    /**
     * programmatic paste 직후 들어오는
     * native paste만 차단
     */
    const nativePasteHandler = function (e) {
        if (!lastProgrammaticPasteAt) {
            return;
        }

        const elapsed =
            performance.now() -
            lastProgrammaticPasteAt;

        if (
            elapsed < 0 ||
            elapsed >= 200
        ) {
            return;
        }

        /**
         * 중복 native paste 차단
         */
        e.preventDefault();
        e.stopImmediatePropagation();

        lastProgrammaticPasteAt = 0;

        if (resetTimer) {
            clearTimeout(resetTimer);
            resetTimer = null;
        }
    };

    textarea.addEventListener(
        'paste',
        nativePasteHandler,
        true
    );

    /**
     * 필요 시 dispose할 수 있도록 저장
     */
    textarea.__pasteWorkaroundDispose =
        function () {

            textarea.removeEventListener(
                'paste',
                nativePasteHandler,
                true
            );

            pasteDisposable.dispose();

            if (resetTimer) {
                clearTimeout(resetTimer);
                resetTimer = null;
            }

            textarea.__pasteWorkaroundRegistered =
                false;

            delete textarea.__pasteWorkaroundDispose;
        };
}

/**
 * Monaco Editor 중복 로드/중복 초기화 방지
 */
(function () {
    const MONACO_BASE_URL = 'https://cdn.jsdelivr.net/npm/monaco-editor@0.52.2/min/vs';
    const MONACO_LOADER_URL = MONACO_BASE_URL + '/loader.js';

    window.__monacoLoaderPromise = window.__monacoLoaderPromise || null;
    window.__monacoReadyPromise = window.__monacoReadyPromise || null;
    window.__fileEditorInstance = window.__fileEditorInstance || null;

    function loadScriptOnce(src) {
        const existing = document.querySelector('script[src="' + src + '"]');

        if (existing) {
            return Promise.resolve();
        }

        return new Promise(function (resolve, reject) {
            const script = document.createElement('script');

            script.src = src;
            script.async = true;

            script.onload = function () {
                resolve();
            };

            script.onerror = function () {
                reject(new Error('Monaco loader load failed: ' + src));
            };

            document.head.appendChild(script);
        });
    }

    window.getMonacoReady = function () {
        if (window.monaco && window.monaco.editor) {
            return Promise.resolve(window.monaco);
        }

        if (window.__monacoReadyPromise) {
            return window.__monacoReadyPromise;
        }

        window.__monacoLoaderPromise = window.__monacoLoaderPromise || loadScriptOnce(MONACO_LOADER_URL);

        window.__monacoReadyPromise = window.__monacoLoaderPromise.then(function () {
            return new Promise(function (resolve, reject) {
                if (window.monaco && window.monaco.editor) {
                    resolve(window.monaco);
                    return;
                }

                if (typeof window.require === 'undefined') {
                    reject(new Error('Monaco require is not available.'));
                    return;
                }

                window.require.config({
                    paths: {
                        vs: MONACO_BASE_URL
                    }
                });

                window.require(
                    ['vs/editor/editor.main'],
                    function () {
                        resolve(window.monaco);
                    },
                    function (err) {
                        reject(err);
                    }
                );
            });
        });

        return window.__monacoReadyPromise;
    };

    window.initFileEditor = function (targetId) {
        return window.getMonacoReady().then(function (monaco) {
            const el = document.getElementById(targetId);

            if (!el) {
                throw new Error('editor element not found: ' + targetId);
            }

            registerCustomFileLinkProvider(monaco);

            /**
             * DOM에 이미 연결된 Monaco가 있으면 무조건 재사용
             */
            if (el.__monacoEditorInstance) {
                window.__fileEditorInstance = el.__monacoEditorInstance;

                registerEditorPasteWorkaround(
                    window.__fileEditorInstance
                );

                registerEditorFollowLinkClickHandler(
                    window.__fileEditorInstance
                );

                return window.__fileEditorInstance;
            }

            /**
             * window에 editor가 존재한다면 실제 DOM과 연결되어 있는지 확인
             */
            if (window.__fileEditorInstance) {
                const editorDom = window.__fileEditorInstance.getDomNode();

                if (
                    editorDom &&
                    document.body.contains(editorDom) &&
                    el.contains(editorDom)
                ) {
                    el.__monacoEditorInstance =
                        window.__fileEditorInstance;

                    registerEditorPasteWorkaround(
                        window.__fileEditorInstance
                    );

                    registerEditorFollowLinkClickHandler(
                        window.__fileEditorInstance
                    );

                    return window.__fileEditorInstance;
                }

                /**
                 * 이전 DOM에서 생성된 stale editor
                 */
                try {
                    window.__fileEditorInstance.dispose();
                } catch (e) {
                    console.warn('old editor dispose failed', e);
                }

                window.__fileEditorInstance = null;
            }

            const editor = monaco.editor.create(el, {
                value: '',
                language: 'plaintext',
                theme: 'vs-dark',
                automaticLayout: true,
                links: true,

                minimap: {
                    enabled: true
                },

                fontSize: 14,
                tabSize: 4,
                wordWrap: 'on',
                scrollBeyondLastLine: false

            }, {
                openerService: {
                    open: function (resource, options) {
                        return handleMonacoFollowLink(
                            resource.toString(),
                            options
                        );
                    }
                }
            });

            /**
             * window + DOM 양쪽에 보관
             */
            window.__fileEditorInstance = editor;
            el.__monacoEditorInstance = editor;

            /**
             * Cmd/Ctrl + V 붙여넣기 중복 방지
             */
            registerEditorPasteWorkaround(editor);

            /**
             * Ctrl/Cmd + Click 파일 링크
             */
            registerEditorFollowLinkClickHandler(editor);

            return editor;
        });
    };
})();
