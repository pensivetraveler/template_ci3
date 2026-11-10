// jQuery repeater 확장: setList(list[, listName])
(function ($) {
    if (typeof $.fn.setList !== 'function') {
        $.fn.setList = function (list, listName) {
            list = Array.isArray(list) ? list : [];

            // 여러 repeater 루트 지원
            return this.each(function () {
                var $root = $(this);

                // 1) 대상 list 컨테이너 결정
                var $lists = $root.find('[data-repeater-list]');
                if (!$lists.length) return;

                var $list = listName
                    ? $lists.filter('[data-repeater-list="' + listName + '"]')
                    : $lists.eq(0);

                if (!$list.length) return;

                var lname = $list.data('repeater-list');

                // 2) 현재 개수 → 원하는 개수로 맞추기 (create/delete 버튼 사용)
                var $create = $root.find('[data-repeater-create]');
                function items() {
                    // 직계 item만 카운트 (중첩/템플릿 제외)
                    return $list.children('[data-repeater-item]');
                }

                var curr = items().length;
                var target = list.length;

                while (curr < target) { $create.trigger('click'); curr++; }
                while (curr > target) {
                    items().last().find('[data-repeater-delete]').trigger('click');
                    curr--;
                }

                // 3) 값 채우기: repeaterVal 있으면 사용, 없으면 수동 매핑
                if (typeof $root.repeaterVal === 'function') {
                    var payload = {};
                    payload[lname] = list;
                    $root.repeaterVal(payload);
                } else {
                    // 수동 매핑(checkbox/radio/select[multiple] 지원)
                    items().each(function (i, el) {
                        var data = list[i] || {};
                        Object.keys(data).forEach(function (k) {
                            var v = data[k];
                            var $inputs = $(el).find('[name$="[' + k + ']"]');

                            $inputs.each(function (_, ip) {
                                var $ip = $(ip);
                                var type = ($ip.attr('type') || '').toLowerCase();

                                if (type === 'checkbox') {
                                    if (Array.isArray(v)) {
                                        $ip.prop('checked', v.map(String).includes(String($ip.val())));
                                    } else {
                                        // 단일 체크박스: true/false, '1'/'0', 값 일치 등 처리
                                        var onVals = ['1', 'true', 'on', $ip.val()];
                                        $ip.prop('checked', onVals.includes(String(v)));
                                    }
                                } else if (type === 'radio') {
                                    $ip.prop('checked', String($ip.val()) === String(v));
                                } else if ($ip.is('select[multiple]')) {
                                    $ip.val(Array.isArray(v) ? v.map(String) : [String(v)]).trigger('change');
                                } else {
                                    $ip.val(v == null ? '' : v).trigger('change');
                                }
                            });
                        });
                    });
                }
            });
        };
    }
})(jQuery);
