/**
 * 메뉴 관리 페이지 스크립트
 */
function flatMenuList(container, depth = 1) {
    return Array.from(container.querySelectorAll(`.menu-item`))
        .map(el => ({
            depth:    parseInt(el.dataset.depth, 10),
            sort:     parseInt(el.dataset.srt, 10),
            title:    el.querySelector('.menu-title').textContent.trim(),
            // 아이콘 정보가 필요하면 아래처럼 추출 가능
            icon:     (() => {
                const i = el.querySelector('.menu-title i');
                return i ? Array.from(i.classList).sort().join(' ') : null;
            })(),
            parentId: (() => {
                return parseInt(el.dataset.depth, 10)===1?0:getIndexInParent(el.parentElement.closest('.menu-item'));
            })(),
        })).sort((a, b) => a.sort - b.sort);
}

document.addEventListener('DOMContentLoaded', function() {
    // 메뉴 데이터 로드
    loadMenuData();

    // 이벤트 리스너 등록
    document.getElementById('addMenuBtn').addEventListener('click', handleAddMenu);
    document.getElementById('saveMenuBtn').addEventListener('click', handleSaveMenu);
    document.getElementById('refreshMenuBtn').addEventListener('click', () => {
        loadMenuData(true)
    });
    document.getElementById('saveCacheBtn').addEventListener('click', handleCacheMenu);
    document.getElementById('addMenuCodeBtn').addEventListener('click', handleAddMenuCode);
    document.getElementById('checkConfigBtn').addEventListener('click', handleCheckConfig);

    // 메뉴 데이터 로드 함수
    function loadMenuData(alert = false) {
        fetch('/admin/api/menuList', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(result => {
            if (result.code === 2001 && Array.isArray(result.data)) {
                renderMenuTree(result.data);
                initializeSortable();
            } else {
                console.error('메뉴 데이터 로드 실패:', result.msg);
            }

            if(alert) {
                showAlert({
                    type: 'success',
                    title: 'Complete',
                    text: 'Your Data Is Refreshed',
                })
            }
        })
        .then(() => {
            if(!deepEqual(flatMenuList(document.getElementById('menuCachedContainer')), flatMenuList(document.getElementById('menuDBContainer'))))
                document.getElementById('notConfEqual').classList.remove('d-none');
        })
        .catch(error => {
            console.error('API 호출 중 오류 발생:', error);
        });
    }

    // 메뉴 트리 렌더링 함수
    function renderMenuTree(menuItems) {
        const container = document.getElementById('menuContainer');
        container.innerHTML = ''; // 컨테이너 초기화

        menuItems.forEach(item => {
            container.appendChild(createMenuItem(item, true));
        });

        const dbContainer = document.getElementById('menuDBContainer');
        dbContainer.innerHTML = ''; // 컨테이너 초기화

        menuItems.forEach(item => {
            dbContainer.appendChild(createMenuItem(item, false));
        });

        // 이벤트 리스너 등록
        attachEventListeners();
    }

    // 메뉴 아이템 생성 함수
    function createMenuItem(item, isButtons = true, isSubMenu = false) {
        const menuItem = document.createElement('div');
        menuItem.className = 'menu-item';
        menuItem.dataset.menuId = item.menu_id;
        menuItem.dataset.depth = item.depth;
        menuItem.dataset.srt = item.srt;
        menuItem.dataset.parentId = item.parent_id;
        menuItem.dataset.isUse = item.is_use;

        const header = document.createElement('div');
        header.className = 'menu-header';

        // 메뉴 제목 (아이콘 포함)
        const title = document.createElement('span');
        title.className = 'menu-title';

        if(isButtons) {
            const code = document.createElement('span');
            code.classList.add('badge', 'me-2');
            if (item.code) {
                code.classList.add('bg-gray');
                code.innerHTML = `${item.code}`;
            } else {
                code.classList.add('bg-danger');
                code.innerHTML = `<span class="d-none d-inline d-md-inline">${getLocale('MenuCode')}</span> ${getLocale('Unregistered')}`;
            }
            title.appendChild(code);
        }

        // 아이콘이 있는 경우에만 아이콘 추가
        if (item.icon) {
            const icon = document.createElement('i');
            icon.className = `icon-base ${item.icon} me-2`;
            title.appendChild(icon);
        }

        // 메뉴 제목 텍스트 추가
        title.appendChild(document.createTextNode(item.title));

        header.appendChild(title);

        if(isButtons) {
            // 메뉴 액션 버튼들
            const actions = document.createElement('div');
            actions.className = 'menu-actions';

            // 메뉴 정보 표시
            if ((item.class && item.method) || item.attr.href) {
                const badge = document.createElement('span');
                badge.classList.add('badge', 'me-2');
                if(item.class && item.method) {
                    badge.classList.add('bg-gray');
                    badge.textContent = `${item.class}/${item.method}`;
                }else{
                    badge.classList.add('bg-warning');
                    badge.textContent = `Redirect`;
                }
                actions.appendChild(badge);
            }

            if(!isSubMenu) {
                // 하위메뉴 추가 버튼
                const addSubmenuBtn = document.createElement('button');
                addSubmenuBtn.className = 'btn btn-sm btn-outline-primary add-submenu';
                addSubmenuBtn.textContent = getLocale('Add New SubMenu', common.LOCALE);
                actions.appendChild(addSubmenuBtn);
            }

            // 수정 버튼
            const editBtn = document.createElement('button');
            editBtn.className = 'btn btn-sm btn-outline-secondary edit-menu';
            editBtn.textContent = getLocale('Edit', common.LOCALE);
            actions.appendChild(editBtn);

            // 삭제 버튼
            const deleteBtn = document.createElement('button');
            deleteBtn.className = 'btn btn-sm btn-outline-danger delete-menu';
            deleteBtn.textContent = getLocale('Delete', common.LOCALE);
            actions.appendChild(deleteBtn);

            header.appendChild(actions);
        }

        menuItem.appendChild(header);

        // 하위메뉴 컨테이너
        const submenuContainer = document.createElement('div');
        submenuContainer.classList.add('menu-container', 'submenu-container');

        // 하위메뉴가 있는 경우 재귀적으로 추가
        if (item.sub_menu && item.sub_menu !== 'a:0:{}') {
            const subMenus = typeof item.sub_menu === 'string' ? 
                JSON.parse(item.sub_menu.replace(/a:\d+:\{/g, '[').replace(/\}/g, ']')) : 
                item.sub_menu;
            
            if (Array.isArray(subMenus)) {
                subMenus.forEach(subItem => {
                    submenuContainer.appendChild(createMenuItem(subItem, isButtons, true));
                });
            }
        }

        menuItem.appendChild(submenuContainer);
        return menuItem;
    }

    // Sortable 초기화 함수
    function initializeSortable() {
        const containers = document.querySelectorAll('.menu-container');
        containers.forEach(container => {
            new Sortable(container, {
                group: {
                    name: 'nested',
                    pull: true,
                    put: function(to, from, dragEl) {
                        // 최상위 컨테이너로는 항상 이동 가능
                        if (!to.el.closest('.menu-item')) return true;
                        return dragEl.querySelectorAll('.menu-item').length === 0;
                    }
                },
                animation: 150,
                fallbackOnBody: true,
                swapThreshold: 0.65,
                ghostClass: 'ghost',
                chosenClass: 'chosen',
                dragClass: 'drag',
                handle: '.menu-title',
                onStart: function(evt) {
                    const item = evt.item;
                    item.removeAttribute('data-depth');

                    if(!item.querySelectorAll('.menu-item').length) {
                        // 드래그 시작 시 depth 1인 메뉴 아이템의 submenu-container만 보이게 설정
                        document.querySelectorAll('[data-depth="1"]>.submenu-container').forEach(container => {
                            container.style.display = 'block';
                            container.style.minHeight = '2rem';
                        });
                    }
                },
                onEnd: function(evt) {
                    const item = evt.item;
                    const fromContainer = evt.from;
                    const toContainer = evt.to;
                    const fromDepth = parseInt(fromContainer.closest('.menu-item')?.dataset.depth || 0);
                    const toDepth = parseInt(toContainer.closest('.menu-item')?.dataset.depth || 0);

                    // 메뉴 아이템의 깊이 업데이트 (최대 depth 2)
                    const newDepth = Math.min(toDepth + 1, 2);
                    item.dataset.depth = newDepth;
                    item.dataset.parentId = toContainer.closest('.menu-item')?.dataset.menuId || '0';

                    // 부모-자식 관계 시각적 업데이트
                    updateMenuVisuals(item, newDepth);

                    // 모든 submenu-container의 표시 상태 업데이트
                    document.querySelectorAll('.menu-item').forEach(menuItem => {
                        const submenuContainer = menuItem.querySelector('.submenu-container');
                        if (submenuContainer) {
                            // 하위메뉴가 있는 경우에만 컨테이너 표시
                            submenuContainer.style.display =
                                submenuContainer.querySelector('.menu-item') ? 'block' : 'none';
                            // 하위메뉴가 없는 경우 minHeight 제거
                            if (!submenuContainer.querySelector('.menu-item')) {
                                submenuContainer.style.minHeight = '';
                            }
                        }
                    });

                    // 메뉴 순서 업데이트
                    updateMenuOrder();
                }
            });
        });
    }

    // 메뉴 시각적 요소 업데이트 함수
    function updateMenuVisuals(menuItem, depth) {
        // 깊이에 따른 스타일 클래스 업데이트
        menuItem.classList.remove('depth-1', 'depth-2', 'depth-3');
        menuItem.classList.add(`depth-${depth}`);

        // 하위메뉴 컨테이너 업데이트는 onEnd에서 처리
    }

    // 메뉴 순서 업데이트 함수
    function updateMenuOrder() {
        const menuItems = document.querySelectorAll('#menuContainer .menu-item');
        const menuOrder = Array.from(menuItems).map(item => ({
            menu_id: item.dataset.menuId,
            parent_id: item.dataset.parentId,
            depth: item.dataset.depth,
            srt: Array.from(item.parentElement.children).indexOf(item) + 1,
        }));

        // TODO: 서버에 순서 업데이트 요청 보내기
        console.log('Updated menu order:', menuOrder);

        executeAjax({
            url: common.API_URI + '/' + 'saveAll',
            method: 'post',
            data: JSON.stringify(menuOrder),
            dataType: 'json',
            success: function(response) {
                showAlert({
                    type: 'success',
                    title: 'Complete',
                    text: response.msg,
                    callback: reload,
                });
            },
        });
    }

    // 이벤트 리스너 등록 함수
    function attachEventListeners() {
        // 하위메뉴 추가 버튼
        document.querySelectorAll('.add-submenu').forEach(btn => {
            btn.addEventListener('click', handleAddSubmenu);
        });

        // 메뉴 수정 버튼
        document.querySelectorAll('.edit-menu').forEach(btn => {
            btn.addEventListener('click', handleEditMenu);
        });

        // 메뉴 삭제 버튼
        document.querySelectorAll('.delete-menu').forEach(btn => {
            btn.addEventListener('click', handleDeleteMenu);
        });
    }

    // 새 메뉴 추가 핸들러
    function handleAddMenu() {
        // TODO: 새 메뉴 추가 모달 표시
        console.log('Add new menu');
        $('[name="depth"]').val(1);
        $('[name="srt"]').val(document.querySelectorAll('#menuContainer .menu-item[data-depth="1"]').length+1);
        readyFrmInputs(formRecord, 'add', common.FORMDATA);
    }

    // 하위메뉴 추가 핸들러
    function handleAddSubmenu(event) {
        const menuItem = event.target.closest('.menu-item');
        const subItems = menuItem.querySelectorAll('.submenu-container .menu-item').length;
        // TODO: 하위메뉴 추가 모달 표시
        console.log('Add submenu to:', menuItem.dataset.menuId);
        $('[name="parent_id"]').val(menuItem.dataset.menuId).trigger('change');
        $('[name="depth"]').val(2);
        $('[name="is_sub_menu"]').val(subItems>0);
        $('[name="srt"]').val(subItems+1);
        readyFrmInputs(formRecord, 'add', common.FORMDATA);
    }

    // 메뉴 수정 핸들러
    function handleEditMenu(event) {
        const menuItem = event.target.closest('.menu-item');
        // TODO: 메뉴 수정 모달 표시
        console.log('Edit menu:', menuItem.dataset.menuId);

        readyFrmInputs(formRecord, 'edit', common.FORMDATA);
        fetchFrmValues(document.querySelector(formSelector), {
            'menu_id' : menuItem.dataset.menuId
        });
    }

    // 메뉴 삭제 핸들러
    function handleDeleteMenu(event) {
        const menuItem = event.target.closest('.menu-item');
        if (confirm('정말로 이 메뉴를 삭제하시겠습니까?')) {
            // TODO: 메뉴 삭제 API 호출
            console.log('Delete menu:', menuItem.dataset.menuId);

            deleteData({
                menu_id: menuItem.dataset.menuId
            })
        }
    }

    // 메뉴 저장 핸들러
    function handleSaveMenu() {
        // TODO: 전체 메뉴 구조 저장 API 호출
        console.log('Save menu structure');

        updateMenuOrder();
    }

    function handleCacheMenu() {
        // TODO: 전체 메뉴 구조 캐싱 API 호출
        console.log('Caching menu structure');

        executeAjax({
            url: common.API_URI + '/' + 'caching',
            success: function(response) {
                showAlert({
                    type: 'success',
                    title: 'Complete',
                    text: response.msg,
                    callback: reload,
                });
            },
        });
    }

    function handleAddMenuCode() {
        // TODO: 전체 메뉴 구조 캐싱 API 호출
        console.log('Caching menu structure');

        executeAjax({
            url: common.API_URI + '/' + 'generateMenuCode',
            success: function(response) {
                showAlert({
                    type: 'success',
                    title: 'Complete',
                    text: response.msg,
                    callback: reload,
                });
            },
        });
    }

    function handleCheckConfig() {
        const wrap = document.querySelectorAll('.menu-check-wrapper [class^="col-md-"]');
        if(document.querySelectorAll('.menu-check-wrapper .col-md-4').length) {
            document.getElementById('checkConfigBtn').textContent = getLocale('Show Config', common.LOCALE);
            wrap.forEach(function (el) {
                if(el.querySelector('#menuConfContainer')) el.classList.add('d-none');
                el.classList.replace('col-md-4', 'col-md-6');
            });
        }else{
            document.getElementById('checkConfigBtn').textContent = getLocale('Hide Config', common.LOCALE);
            wrap.forEach(function (el) {
                el.classList.remove('d-none');
                el.classList.replace('col-md-6', 'col-md-4');
            });
        }
    }

    //// offcanvase

    const formSelector = '#formRecord';
    const offCanvasElement = document.querySelector('#offcanvasRecord');

    const formRecord = document.querySelector(formSelector);
    if(formRecord === null) throw new Error(`formRecord is not exist`);
    preparePlugins(formRecord);

    offCanvasEl = new bootstrap.Offcanvas(offCanvasElement);

    offCanvasElement.addEventListener('show.bs.offcanvas', function(e) {
        console.log('offcanvas show');
        refreshPlugins(formRecord);
        document.getElementById('offcanvasLabel').textContent = getLocale(`${capitalize(this.querySelector(formSelector)['_mode'].value)} Record`, common.LOCALE);
    });

    offCanvasElement.addEventListener('shown.bs.offcanvas', function(e) {
        console.log('offcanvas shown');
    });

    offCanvasElement.addEventListener('hide.bs.offcanvas', function(e) {
        console.log('offcanvas hide');
    });

    offCanvasElement.addEventListener('hidden.bs.offcanvas', function(e) {
        resetFrmInputs(document.querySelector(formSelector), common.EXTRA_FORMDATA);
        fv.resetForm(true);

        if ($('[data-repeater-item]').length) {
            $('[data-repeater-item]').each(function (i, v) {
                if(i > 0) $(v).remove();
            });
        }
    });

    formRecord.addEventListener('readyFrmInputs', (e) => {
        if(formRecord._mode.value !== 'edit') offCanvasEl.show();
    });

    formRecord.addEventListener("fetchFrmValues", (e) => {
        applyFrmValues(formRecord, record, common.FORM_DATA);
        offCanvasEl.show();
    });

    formRecord.addEventListener('transFrmValues', (e) => {
        offCanvasEl.hide();
    });

    // Form validation for Add new record
    fv = FormValidation.formValidation(
        formRecord,
        {
            fields: reformatFormData(formRecord, common.EXTRA_FORMDATA, common.FORM_REGEXP, true),
            plugins: {
                trigger: new FormValidation.plugins.Trigger(),
                bootstrap5: new FormValidation.plugins.Bootstrap5({
                    // Use this for enabling/changing valid/invalid class
                    // eleInvalidClass: '',
                    eleValidClass: '',
                    rowSelector: function(field, ele) {
                        switch (field) {
                            default:
                                return '.form-validation-unit';
                        }
                    },
                }),
                submitButton: new FormValidation.plugins.SubmitButton(),
                // submit button의 type을 submit으로 원할 경우
                // defaultSubmit: new FormValidation.plugins.DefaultSubmit(),
                autoFocus: new FormValidation.plugins.AutoFocus(),
            },
            init: instance => {
                instance.on('plugins.message.placed', function (e) {
                    //* Move the error message out of the `input-group` element
                    if (e.element.parentElement.classList.contains('input-group')) {
                        // `e.field`: The field name
                        // `e.messageElement`: The message element
                        // `e.element`: The field element
                        e.element.parentElement.insertAdjacentElement('afterend', e.messageElement);
                    }
                });
            }
        }
    ).on('plugins.message.displayed', function (event) {
        // e.messageElement presents the error message element
    }).on('core.field.init', function(event) {
        // When a field is initialized, bind the input event to it
        var field = event.field;
        var element = event.elements[0];  // The field element
        element.addEventListener('change', function() {
            // Revalidate field when flatpickr
            if(element.classList.contains('.form-input_date-flatpickr')) fv.revalidateField(field);
            // Revalidate field whenever input changes
            // e.fv.revalidateField(field);
        });
    }).on('core.form.validating', function(event) {
        // 유효성 검사 시작 전
        console.log('%c The form validation has started.', 'color: green')
        const form = event.formValidation.form;
        if(form['_event'] !== undefined) form['_event'].value = 'submit';
    }).on('core.validator.validating', function(event) {
        // 특정 요소에 대한 유효성 검사 시작 전
        console.log('============================================================');
        console.log('%c Validator for the field ' + event.field + ' is validating.', 'color: skyblue');
        if(event.element.hasAttribute('data-textarea-id')) {
            if(event.element.getAttribute('data-textarea-id')) {
                const textareaId = event.element.getAttribute('data-textarea-id');
                event.element.value = editors[`${textareaId}`].root.innerHTML;
            }
        }
        console.log('value : ', event.element.value);
    }).on('core.validator.validated', function(event) {
        // 특정 요소에 대한 유효성 검사 시작 후
        console.log('%c Validator for the field ' + event.field + ' is validated.', 'color: skyblue');
        if(!event.result.valid) {
            console.log('------------------------------------------------------------');
            console.log('%c Validator for the field ' + event.field + ' is invalid.', 'color: red');
            console.log('Invalid validator:', event.validator);
            console.log('Invalid field:', event.field);
            console.log('Error message:', event.result.message);
            console.log('Result Object:',event.result)
            console.log('------------------------------------------------------------');
        }
    }).on('core.form.valid', function(event) {
        // 유효성 검사 완료 후
        updateFormLifeCycle('checkFrmValues', formRecord);

        // Send the form data to back-end
        // You need to grab the form data and create an Ajax request to send them
        submitAjax(formSelector, {
            success: function(response) {
                showAlert({
                    type: 'success',
                    title: 'Complete',
                    text: formRecord['_mode'].value === 'edit' ? 'Your Data Is Updated' : 'Registered Successfully',
                    callback: loadMenuData,
                });
                updateFormLifeCycle('transFrmValues', formRecord);
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.warn(jqXHR.responseJSON)
                if(jqXHR.status === 422) {
                    jqXHR.responseJSON.errors.forEach(error => {
                        if(Object.hasOwn(fv.fields, error.param)) {
                            fv.updateFieldStatus(error.param, 'Invalid', customValidatorsPreset.inflector(error.type));
                        }
                    });
                }else{
                    showAlert({
                        type: 'warning',
                        text: jqXHR.responseJSON.msg,
                    });
                }
            }
        }, true);
    }).on('core.form.invalid', function () {
        // if fields are invalid
        console.log('core.form.invalid')
    });
});
