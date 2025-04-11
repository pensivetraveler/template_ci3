const Helpers = {
    // Tabs animation
    navTabsAnimation() {
        // Adding timeout to make it work on firefox
        setTimeout(() => {
            document.querySelectorAll('.nav-tabs').forEach(tab => {
                let slider = tab.querySelector('.tab-slider')
                if (!slider) {
                    const sliderEle = document.createElement('span')
                    sliderEle.setAttribute('class', 'tab-slider')

                    slider = tab.appendChild(sliderEle)
                }
                const isVertical = tab.closest('.nav-align-left') || tab.closest('.nav-align-right')
                const setSlider = activeTab => {
                    const tabsEl = activeTab.parentElement
                    const tabsRect = tabsEl.getBoundingClientRect()
                    const activeTabRect = activeTab.getBoundingClientRect()
                    const sliderStart = activeTabRect.x - tabsRect.x
                    const isBottom = tab.closest('.nav-align-bottom')
                    if (isVertical) {
                        slider.style.top = activeTabRect.y - tabsRect.y + 'px'
                        slider.style[tab.closest('.nav-align-right') ? 'inset-inline-start' : 'inset-inline-end'] = 0
                        slider.style.height = activeTabRect.height + 'px'
                    } else {
                        slider.style.left = sliderStart + 'px'
                        slider.style.width = activeTabRect.width + 'px'
                        if (!isBottom) {
                            slider.style.bottom = 0
                        }
                    }
                }
                // On click
                tab.addEventListener('click', event => {
                    // To avoid active state for disabled element
                    if (event.target.closest('.nav-item .active')) {
                        setSlider(event.target.closest('.nav-item'))
                    }
                })
                // On Load
                setSlider(tab.querySelector('.nav-link.active').closest('.nav-item'))
            })
        }, 50)
    },
};