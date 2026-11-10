'use strict';

const VisitorCharts = (function () {
    let cardColor, headingColor, labelColor, borderColor, legendColor;

    const purpleColor = '#836AF9',
        orangeColor = '#FF8132',
        greyColor = '#4F5D70';

    const chartColors = {
        column: {
            series: [
                '#D2B0FF',
                '#826AF9',
                '#FF8132',
                '#29DAC7',
                '#32BAFF',
                '#FDD835',
                '#4F5D70',
                '#FFA1A1',
                '#7367F0',
                '#00CFE8'
            ],
            bg: '#F8D3FF'
        },

        donut: {
            series: [
                '#fdd835',
                '#32baff',
                '#ffa1a1',
                '#7367f0',
                '#29dac7'
            ]
        },

        area: {
            series: [
                '#ab7efd',
                '#b992fe',
                '#e0cffe'
            ]
        }
    };

    const instances = {};

    function initThemeColors() {
        if (isDarkStyle) {
            cardColor = config.colors_dark.cardColor;
            headingColor = config.colors_dark.headingColor;
            labelColor = config.colors_dark.textMuted;
            legendColor = config.colors_dark.bodyColor;
            borderColor = config.colors_dark.borderColor;
        } else {
            cardColor = config.colors.cardColor;
            headingColor = config.colors.headingColor;
            labelColor = config.colors.textMuted;
            legendColor = config.colors.bodyColor;
            borderColor = config.colors.borderColor;
        }
    }

    function deepMerge(target, ...sources) {
        sources.forEach(source => {
            if (!source) return;

            Object.keys(source).forEach(key => {
                const value = source[key];

                if (
                    value &&
                    typeof value === 'object' &&
                    !Array.isArray(value)
                ) {
                    target[key] = deepMerge(target[key] || {}, value);
                } else {
                    target[key] = value;
                }
            });
        });

        return target;
    }

    function generateSeriesColor(index) {
        const hue = (260 + index * 47) % 360;
        const saturation = 78;
        const lightness = index % 2 === 0 ? 58 : 66;

        return `hsl(${hue}, ${saturation}%, ${lightness}%)`;
    }

    function getPaletteColors(name = 'column') {
        const palette = chartColors[name];

        if (!palette) return [];

        if (Array.isArray(palette)) {
            return palette;
        }

        if (Array.isArray(palette.series)) {
            return palette.series;
        }

        /*
         * 기존 series1, series2 구조도 지원하고 싶을 때 사용
         */
        return Object.keys(palette)
            .filter(key => /^series\d+$/.test(key))
            .sort((a, b) => {
                return Number(a.replace('series', '')) - Number(b.replace('series', ''));
            })
            .map(key => palette[key]);
    }

    function getSeriesCount(series, fallback = 1) {
        return Array.isArray(series) && series.length > 0
            ? series.length
            : fallback;
    }

    function getSeriesColors(seriesOrCount, paletteName = 'column') {
        const count = Array.isArray(seriesOrCount)
            ? seriesOrCount.length
            : Number(seriesOrCount || 0);

        const palette = getPaletteColors(paletteName);
        const colors = [];

        for (let i = 0; i < count; i++) {
            colors.push(palette[i] || generateSeriesColor(i));
        }

        return colors;
    }

    function getTotalsFromSeries(series = []) {
        if (!Array.isArray(series) || series.length === 0) return [];

        const maxLength = Math.max(...series.map(item => item.data?.length || 0));

        return Array.from({ length: maxLength }, (_, dataIndex) => {
            return series.reduce((sum, item) => {
                return sum + Number(item.data?.[dataIndex] || 0);
            }, 0);
        });
    }

    function getEl(selector) {
        return typeof selector === 'string'
            ? document.querySelector(selector)
            : selector;
    }

    function render(key, selector, options) {
        const el = getEl(selector);

        if (!el) return null;

        if (instances[key]) {
            instances[key].destroy();
        }

        const chart = new ApexCharts(el, options);
        chart.render();

        instances[key] = chart;

        return chart;
    }

    function update(key, payload) {
        const chart = instances[key];

        if (!chart) return null;

        if (payload.series) {
            chart.updateSeries(payload.series);
        }

        if (payload.options) {
            chart.updateOptions(payload.options);
        }

        return chart;
    }

    /**
     * 차트가 없으면 최초 생성하고,
     * 이미 존재하면 기존 인스턴스를 updateOptions로 갱신한다.
     *
     * @param {string} key
     * @param {string|HTMLElement} selector
     * @param {object} options
     * @param {boolean} animate
     * @returns {ApexCharts|Promise|null}
     */
    function updateChart(
        key,
        selector,
        options,
        animate = true
    ) {
        const chart = instances[key];

        /*
         * 아직 차트가 생성되지 않았다면 최초 렌더링
         */
        if (!chart) {
            return render(key, selector, options);
        }

        /*
         * series를 포함한 전체 옵션을 한 번에 갱신한다.
         *
         * redrawPaths: false
         * animate: true
         * updateSyncedCharts: false
         */
        return chart.updateOptions(
            options,
            false,
            animate,
            false
        );
    }

    function destroy(key) {
        if (!instances[key]) return;

        instances[key].destroy();
        delete instances[key];
    }

    function addTotalBackgroundSeries(chartData, maxTotal = null) {
        const series = chartData.series || [];

        if (!series.length) {
            return chartData;
        }

        const length = chartData.xaxis?.categories?.length || 0;

        const totals = Array.from({ length }, (_, dataIndex) => {
            return series.reduce((sum, item) => {
                return sum + Number(item.data?.[dataIndex] || 0);
            }, 0);
        });

        const targetTotal = maxTotal ?? Math.max(...totals);

        const totalGapData = totals.map(total => {
            // 실제 데이터가 없는 x축이면 배경 막대도 만들지 않음
            if (total <= 0) return 0;

            return Math.max(targetTotal - total, 0);
        });

        return {
            ...chartData,
            series: [
                ...series,
                {
                    name: 'Total',
                    data: totalGapData,
                    isTotalBackground: true,
                }
            ],
            totals: totals,
        };
    }

    function truncateLabel(value, maxLength = 24) {
        value = String(value ?? '');

        if (value.length <= maxLength) {
            return value;
        }

        return value.substring(0, maxLength) + '...';
    }

    function alignHorizontalBarLabels(chartEl) {
        if (!chartEl) return;

        const paddingLeft = 12;

        const bars = chartEl.querySelectorAll('.apexcharts-bar-area');
        const labels = chartEl.querySelectorAll('.apexcharts-data-labels text');

        if (!bars.length || !labels.length) return;

        labels.forEach((label, index) => {
            const bar = bars[index];

            if (!bar) return;

            const box = bar.getBBox();

            label.setAttribute('x', box.x + paddingLeft);
            label.setAttribute('text-anchor', 'start');
            label.style.textAnchor = 'start';
            label.style.pointerEvents = 'none';

            // bar가 너무 짧으면 라벨이 밖으로 삐져나갈 수 있으므로 숨김 처리
            // if (box.width < 40) {
            //     label.style.display = 'none';
            // } else {
                label.style.display = '';
            // }
        });
    }

    function getLineAreaOptions(customOptions = {}) {
        const seriesCount = getSeriesCount(customOptions.series, 1);

        const totalValues = Array.isArray(customOptions.totals)
            ? customOptions.totals
            : getTotalsFromSeries(customOptions.series);

        const defaultOptions = {
            chart: {
                height: 400,
                fontFamily: 'Inter',
                type: 'area',
                parentHeightOffset: 0,
                toolbar: {
                    show: false
                },
                stacked: true,
                dropShadow: {
                    enabled: true,
                    top: 2,
                    bottom: 0,
                    left: 0,
                    blur: 4,
                    opacity: 0.1
                }
            },
            dataLabels: {
                enabled: false,
                enabledOnSeries: [seriesCount - 1],
                formatter: function (val, opts) {
                    const total = totalValues[opts.dataPointIndex];

                    return total === undefined
                        ? ''
                        : Number(total).toLocaleString();
                },
                offsetY: -8,
                style: {
                    fontSize: '12px',
                    fontWeight: 600,
                    colors: [legendColor]
                },
                background: {
                    enabled: false
                },
                dropShadow: {
                    enabled: false
                }
            },
            stroke: {
                show: false,
                curve: 'straight'
            },
            legend: {
                show: true,
                position: 'top',
                horizontalAlign: 'start',
                fontSize: '13px',
                markers: {
                    width: 10,
                    height: 10
                },
                labels: {
                    colors: legendColor,
                    useSeriesColors: false
                }
            },
            grid: {
                borderColor: borderColor,
                xaxis: {
                    lines: {
                        show: true
                    }
                }
            },
            colors: getSeriesColors(seriesCount, 'column'),
            series: [],
            xaxis: {
                categories: [],
                axisBorder: {
                    show: false
                },
                axisTicks: {
                    show: false
                },
                labels: {
                    style: {
                        colors: labelColor,
                        fontSize: '13px'
                    }
                }
            },
            yaxis: {
                labels: {
                    style: {
                        colors: labelColor,
                        fontSize: '13px'
                    }
                }
            },
            fill: {
                opacity: 1,
                type: 'solid'
            },
            tooltip: {
                shared: true,
                intersect: false,
                custom: function ({
                    series,
                    dataPointIndex,
                    w
                }) {
                    const xAxisLabel = getXAxisTooltipLabel(
                        w,
                        dataPointIndex
                    );

                    const total =
                        totalValues[dataPointIndex] || 0;

                    let rows = '';

                    w.globals.seriesNames.forEach(
                        function (name, index) {
                            const value =
                                series[index]?.[dataPointIndex] || 0;

                            rows += `
                                <div style="
                                    display:flex;
                                    justify-content:space-between;
                                    gap:16px;
                                ">
                                    <span>${escapeTooltipHtml(name)}</span>
                                    <strong>
                                        ${Number(value).toLocaleString()}
                                    </strong>
                                </div>
                            `;
                        }
                    );

                    return `
                        <div class="apexcharts-tooltip-custom">
                            <div
                                class="apexcharts-tooltip-title"
                                style="
                                    padding:8px 10px;
                                    font-weight:600;
                                    border-bottom:1px solid ${borderColor};
                                "
                            >
                                ${escapeTooltipHtml(xAxisLabel)}
                            </div>
            
                            <div style="padding:8px 10px;">
                                ${rows}
            
                                <div style="
                                    display:flex;
                                    justify-content:space-between;
                                    gap:16px;
                                    margin-top:6px;
                                    padding-top:6px;
                                    border-top:1px solid ${borderColor};
                                ">
                                    <span>Total</span>
                                    <strong>
                                        ${Number(total).toLocaleString()}
                                    </strong>
                                </div>
                            </div>
                        </div>
                    `;
                }
            },
        };

        return deepMerge({}, defaultOptions, customOptions);
    }

    function getVerticalBarOptions(customOptions = {}) {
        const seriesCount = getSeriesCount(customOptions.series, 1);

        const defaultOptions = {
            chart: {
                height: 400,
                fontFamily: 'Inter',
                type: 'bar',
                stacked: true,
                parentHeightOffset: 0,
                toolbar: {
                    show: false
                }
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '25%',
                    colors: {
                        backgroundBarColors: Array(customOptions.xaxis.categories.length).fill(chartColors.column.bg),
                        backgroundBarRadius: 5
                    },
                    // borderRadius: 10,
                    // borderRadiusApplication: 'end',
                    // borderRadiusWhenStacked: 'last',
                    dataLabels: {
                        total: {
                            enabled: true,
                            offsetY: -5,
                            style: {
                                fontSize: '13px',
                                fontWeight: 600,
                                color: legendColor
                            }
                        }
                    }
                }
            },
            dataLabels: {
                enabled: false
            },
            legend: {
                show: true,
                position: 'top',
                horizontalAlign: 'start',
                fontSize: '13px',
                markers: {
                    width: 10,
                    height: 10
                },
                labels: {
                    colors: legendColor,
                    useSeriesColors: false
                }
            },
            colors: getSeriesColors(seriesCount, 'column'),
            stroke: {
                show: true,
                colors: ['transparent']
            },
            grid: {
                borderColor: borderColor,
                yaxis: {
                    lines: {
                        show: true
                    }
                },
                xaxis: {
                    lines: {
                        show: false
                    }
                },
                padding: {
                    top: 0,
                    left: 10,
                    right: 10,
                    bottom: 10
                }
            },
            series: [],
            xaxis: {
                categories: [],
                axisBorder: {
                    show: false
                },
                axisTicks: {
                    show: false
                },
                labels: {
                    style: {
                        colors: labelColor,
                        fontSize: '13px'
                    }
                }
            },
            yaxis: {
                labels: {
                    style: {
                        colors: labelColor,
                        fontSize: '13px'
                    }
                }
            },
            fill: {
                opacity: 1
            },
            responsive: [
                {
                    breakpoint: 576,
                    options: {
                        chart: {
                            height: 320
                        },
                        plotOptions: {
                            bar: {
                                columnWidth: '45%'
                            }
                        }
                    }
                },
                {
                    breakpoint: 420,
                    options: {
                        chart: {
                            height: 280
                        },
                        plotOptions: {
                            bar: {
                                columnWidth: '55%'
                            }
                        }
                    }
                }
            ],
            tooltip: {
                shared: true,
                intersect: false,
                custom: function ({ series, seriesIndex, dataPointIndex, w }) {
                    const category = w.globals.labels[dataPointIndex];

                    let total = 0;
                    let rows = '';

                    w.globals.seriesNames.forEach((name, index) => {
                        const value = series[index][dataPointIndex] || 0;
                        total += value;

                        rows += `
                <div style="display:flex;justify-content:space-between;gap:16px;">
                    <span>${name}</span>
                    <strong>${value}</strong>
                </div>
            `;
                    });

                    return `
            <div class="apexcharts-tooltip-custom" style="padding:8px 10px;">
                <div style="font-weight:600;margin-bottom:6px;">${category}</div>
                ${rows}
                <div style="border-top:1px solid #ddd;margin-top:6px;padding-top:6px;display:flex;justify-content:space-between;gap:16px;">
                    <span>Total</span>
                    <strong>${total}</strong>
                </div>
            </div>
        `;
                }
            }
        };

        return deepMerge({}, defaultOptions, customOptions);
    }

    function getHorizontalBarOptions(customOptions = {}) {
        const seriesCount = getSeriesCount(customOptions.series, 1);

        const defaultOptions = {
            chart: {
                height: 400,
                fontFamily: 'Inter',
                type: 'bar',
                toolbar: {
                    show: false
                },
                events: {
                    mounted: function (chartContext) {
                        alignHorizontalBarLabels(chartContext.el);
                    },
                    updated: function (chartContext) {
                        alignHorizontalBarLabels(chartContext.el);
                    }
                }
            },

            plotOptions: {
                bar: {
                    horizontal: true,
                    barHeight: '30%',
                    startingShape: 'rounded',
                    borderRadius: 8,

                    dataLabels: {
                        position: 'center'
                    }
                }
            },

            grid: {
                borderColor: borderColor,
                xaxis: {
                    lines: {
                        show: false
                    }
                },
                padding: {
                    top: 0,
                    bottom: 10,
                }
            },

            colors: getSeriesColors(seriesCount, 'column'),

            dataLabels: {
                enabled: true,
                formatter: function (val, opts) {
                    const label = opts.w.globals.labels[opts.dataPointIndex] ?? '';
                    return truncateLabel(label, 40);
                },
                textAnchor: 'start',
                offsetX: 0,
                offsetY: 0,
                style: {
                    fontSize: '14px',
                    fontWeight: 700,
                    colors: ['#000']
                },
                background: {
                    enabled: false
                },
                dropShadow: {
                    enabled: false
                }
            },

            series: [],

            xaxis: {
                categories: [],
                axisBorder: {
                    show: false
                },
                axisTicks: {
                    show: false
                },
                labels: {
                    style: {
                        colors: labelColor,
                        fontSize: '13px'
                    }
                }
            },

            yaxis: {
                labels: {
                    show: false
                }
            }
        };

        return deepMerge({}, defaultOptions, customOptions);
    }

    function getDonutOptions(customOptions = {}) {
        const defaultOptions = {
            chart: {
                height: 400,
                fontFamily: 'Inter',
                type: 'donut',
                dropShadow: {
                    enabled: true,
                    top: 2,
                    left: 1,
                    right: 1,
                    bottom: 1,
                    blur: 4,
                    opacity: 0.2
                }
            },
            labels: [],
            series: [],
            colors: chartColors.donut.series,
            stroke: {
                show: false,
                curve: 'straight'
            },
            dataLabels: {
                enabled: true,
                formatter: function (val) {
                    return parseInt(val, 10) + '%';
                },
                style: {
                    fontSize: '15px',
                    fontWeight: 'normal'
                },
                dropShadow: {
                    enabled: false
                }
            },
            legend: {
                show: true,
                position: 'bottom',
                fontSize: '13px',
                markers: {
                    offsetX: -3,
                    width: 10,
                    height: 10
                },
                itemMargin: {
                    vertical: 3,
                    horizontal: 10
                },
                labels: {
                    colors: legendColor,
                    useSeriesColors: false
                }
            },
            plotOptions: {
                pie: {
                    donut: {
                        labels: {
                            show: true,
                            name: {
                                fontSize: '2rem'
                            },
                            value: {
                                fontSize: '0.9375rem',
                                fontWeight: 500,
                                color: legendColor,
                                formatter: function (val) {
                                    return parseInt(val, 10) + '%';
                                }
                            },
                            total: {
                                show: true,
                                fontSize: '0.9375rem',
                                fontWeight: 500,
                                color: headingColor,
                                label: 'Total',
                                formatter: function (w) {
                                    const total = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                    return total;
                                }
                            }
                        }
                    }
                }
            },
            responsive: [
                {
                    breakpoint: 992,
                    options: {
                        chart: {
                            height: 380
                        },
                        legend: {
                            position: 'bottom',
                            labels: {
                                colors: legendColor,
                                useSeriesColors: false
                            }
                        }
                    }
                },
                {
                    breakpoint: 576,
                    options: {
                        chart: {
                            height: 320
                        },
                        legend: {
                            position: 'bottom',
                            labels: {
                                colors: legendColor,
                                useSeriesColors: false
                            }
                        }
                    }
                },
                {
                    breakpoint: 420,
                    options: {
                        chart: {
                            height: 280
                        },
                        legend: {
                            show: false
                        }
                    }
                },
                {
                    breakpoint: 360,
                    options: {
                        chart: {
                            height: 250
                        },
                        legend: {
                            show: false
                        }
                    }
                }
            ]
        };

        return deepMerge({}, defaultOptions, customOptions);
    }

    function getPolarOptions(customOptions = {}) {
        const defaultOptions = {
            series: [],
            labels: [],

            chart: {
                type: 'polarArea',
                height: 390,
                fontFamily: 'Inter',
                dropShadow: {
                    enabled: true,
                    top: 2,
                    left: 1,
                    right: 1,
                    bottom: 1,
                    blur: 4,
                    opacity: 0.2
                }
            },

            colors: [
                purpleColor,
                orangeColor,
                greyColor
            ],

            stroke: {
                width: 0
            },

            fill: {
                opacity: 1
            },

            yaxis: {
                show: false
            },

            dataLabels: {
                enabled: false
            },

            legend: {
                show: true,
                position: 'bottom',
                fontSize: '13px',
                markers: {
                    offsetX: -3,
                    width: 10,
                    height: 10
                },
                itemMargin: {
                    vertical: 3,
                    horizontal: 10
                },
                labels: {
                    colors: legendColor,
                    useSeriesColors: false
                }
            },

            plotOptions: {
                polarArea: {
                    rings: {
                        strokeWidth: 0
                    },
                    spokes: {
                        strokeWidth: 0
                    }
                }
            },

            tooltip: {
                enabled: false
            },

            responsive: [
                {
                    breakpoint: 992,
                    options: {
                        chart: {
                            height: 380
                        },
                        legend: {
                            position: 'bottom',
                            labels: {
                                colors: legendColor,
                                useSeriesColors: false
                            }
                        }
                    }
                },
                {
                    breakpoint: 576,
                    options: {
                        chart: {
                            height: 320
                        },
                        legend: {
                            position: 'bottom',
                            labels: {
                                colors: legendColor,
                                useSeriesColors: false
                            }
                        }
                    }
                },
                {
                    breakpoint: 420,
                    options: {
                        chart: {
                            height: 280
                        },
                        legend: {
                            show: false
                        }
                    }
                },
                {
                    breakpoint: 360,
                    options: {
                        chart: {
                            height: 250
                        },
                        legend: {
                            show: false
                        }
                    }
                }
            ]
        };

        return deepMerge({}, defaultOptions, customOptions);
    }

    function escapeTooltipHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    /**
     * 현재 데이터 포인트에 대응하는 x축 표시 라벨
     */
    function getXAxisTooltipLabel(w, dataPointIndex) {
        const rawValue =
            w.config?.xaxis?.categories?.[dataPointIndex]
            ?? w.globals?.labels?.[dataPointIndex]
            ?? w.globals?.seriesX?.[0]?.[dataPointIndex]
            ?? '';

        const formatter =
            w.config?.xaxis?.labels?.formatter;

        /*
         * x축에 별도의 formatter를 사용하고 있다면
         * tooltip 제목에도 동일하게 적용
         */
        if (typeof formatter === 'function') {
            try {
                return formatter(rawValue);
            } catch (error) {
                console.warn(
                    'xaxis label formatter failed:',
                    error
                );
            }
        }

        return rawValue;
    }

    function init() {
        initThemeColors();
    }

    return {
        init,
        render,
        update,
        updateChart,
        destroy,
        getLineAreaOptions,
        getVerticalBarOptions,
        getHorizontalBarOptions,
        getDonutOptions,
        getPolarOptions,
        getSeriesColors,
        addTotalBackgroundSeries
    };
})();
