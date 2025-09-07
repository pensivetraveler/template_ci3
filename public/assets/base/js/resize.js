function setUserAgentPropeties() {
    if(isMobile()) {
        document.body.classList.add('md-mobile');
    }else{
        if(window.innerWidth <= 600) {
            document.body.classList.add('md-mobile');
        }else{
            if(!window.matchMedia('(orientation: portrait)').matches) document.body.classList.remove('md-mobile');
        }
    }

    const md = new MobileDetect(window.navigator.userAgent);
    if(md.tablet() !== null) document.body.classList.add('md-tablet');

    document.body.setAttribute('userAgent', navigator.userAgent);
    if(navigator.userAgent.match(/iPhone/i) || navigator.userAgent.match(/iPad/i)){
        replaceCssFile(commonCssSelector, commonCssFilename+'.safari');
    }else{
        replaceCssFile(commonCssSelector, commonCssFilename);
    }
}

function setScreenSizeUnit() {
    const vw = window.innerWidth * 0.01;
    const vh = window.innerHeight * 0.01;

    document.documentElement.style.setProperty('--vh', `${vh}px`);
    document.documentElement.style.setProperty('--vw', `${vw}px`);

    document.body.setAttribute('data-vh', vh.toString());
    document.body.setAttribute('data-vw', vw.toString());

    if (window.matchMedia('(orientation: portrait)').matches) {
        // Portrait 모드일 때 실행할 스크립트, 폭과 높이가 같으면 Portrait 모드로 인식
        landscape = false;
    } else {
        // Landscape 모드일 때 실행할 스크립트
        landscape = true;
    }

    if(document.body.classList.contains('md-mobile')){
        (landscape)?document.body.classList.add('landscape'):document.body.classList.remove('landscape');
    }

    window.screenVars = {
        landscape:landscape,
        zoomRatio:1,
        vw:vw,
        vw:vh,
        vwScreen:landscape?vw:vh,
        vhScreen:landscape?vh:vw,
        fontSize:10,
    };
}

function resizeScaleByFontSize(node) {
    const screenMaxWidth = designWidth?designWidth:window.innerWidth;
    const screenMaxHeight = designHeight?designHeight:window.innerHeight;
    const screenMaxRatio = screenMaxWidth/screenMaxHeight;

    if(designWidth){
        containerWidth = Math.min(window.screenVars.vwScreen*100, screenMaxWidth);
        window.screenVars.zoomRatio = Math.min((window.screenVars.vwScreen*100)/screenMaxWidth, 1);
        containerHeight = window.screenVars.vhScreen*100;
    }
    if(designHeight){
        containerWidth = window.screenVars.vwScreen*100;
        window.screenVars.zoomRatio = Math.min((window.screenVars.vhScreen*100)/screenMaxWidth, 1);
        containerHeight = Math.min(window.screenVars.vhScreen*100, screenMaxWidth);
    }

    node.style.width = containerWidth+'px';
    node.style.height = containerHeight+'px';

    if(window.screenVars.landscape){
        node.style.rotate = '0deg';
        node.style.top = `50%`;
        node.style.left = `50%`;
    }else{
        node.style.rotate = '90deg';
        node.style.top = `calc(50% + ${(containerWidth-containerHeight)/2}px)`;
        node.style.left = `calc(-50% - ${(containerWidth-containerHeight)/2}px)`;
    }

    window.screenVars.fontSize = window.screenVars.fontSize * window.screenVars.zoomRatio;

    document.querySelector('html').style.fontSize = window.screenVars.fontSize.toString()+'px';

    replaceCssFile(layoutCssSelector, layoutCssFilename);

    return window.screenVars.zoomRatio = 1;
}

function resizeScaleByZoomRatio(node) {
    const screenMaxWidth = designWidth?designWidth:window.innerWidth;
    const screenMaxHeight = designHeight?designHeight:window.innerHeight;
    const screenMaxRatio = screenMaxWidth/screenMaxHeight;

    if(!designWidth || !designHeight) {
        if(designWidth){
            containerWidth = Math.min(window.screenVars.vwScreen*100, screenMaxWidth);
            window.screenVars.zoomRatio = Math.min((window.screenVars.vwScreen*100)/screenMaxWidth, 1);
            containerHeight = window.screenVars.vhScreen*100/window.screenVars.zoomRatio;

            node.style.width = screenMaxWidth + 'px';
            node.style.height = containerHeight + 'px';

            if(window.screenVars.landscape){
                node.style.rotate = '0deg';
                node.style.top = `50%`;
                node.style.left = `50%`;
            }else{
                node.style.rotate = '90deg';
                node.style.top = `calc(50% + ${(screenMaxWidth-containerHeight)/2}px)`;
                node.style.left = `calc(-50% - ${(screenMaxWidth-containerHeight)/2}px)`;
            }
        }
        if(designHeight){
            containerHeight = Math.min(window.screenVars.vhScreen*100, screenMaxHeight);
            window.screenVars.zoomRatio = Math.min((window.screenVars.vhScreen*100)/screenMaxHeight, 1);
            containerWidth = window.screenVars.vhScreen*100/window.screenVars.zoomRatio;

            node.style.width = containerWidth + 'px';
            node.style.height = screenMaxHeight + 'px';

            if(window.screenVars.landscape){
                node.style.rotate = '90deg';
                node.style.top = `calc(50% + ${(screenMaxWidth-containerHeight)/2}px)`;
                node.style.left = `calc(-50% - ${(screenMaxWidth-containerHeight)/2}px)`;
            }else{
                node.style.rotate = '0deg';
                node.style.top = `50%`;
                node.style.left = `50%`;
            }
        }
        window.screenVars.fontSize = 10 * window.screenVars.zoomRatio;
    }else {
        if (window.screenVars.vwScreen / window.screenVars.vhScreen > screenMaxRatio) {
            // 세로 비율에 맞춘다.
            containerHeight = Math.min(window.screenVars.vhScreen * 100, screenMaxHeight);
            window.screenVars.zoomRatio = Math.min((window.screenVars.vhScreen * 100) / screenMaxHeight, 1);
            // 세로 비율에 맞췄을 때 가로는?
            containerWidth = Math.min(window.screenVars.vhScreen * screenMaxRatio * 100, screenMaxWidth);
        } else {
            // 가로 비율에 맞춘다.
            containerWidth = Math.min(window.screenVars.vwScreen * 100, screenMaxWidth);
            window.screenVars.zoomRatio = Math.min((window.screenVars.vwScreen * 100) / screenMaxWidth, 1);
            // 가로 비율에 맞췄을 때 세로는?
            containerHeight = Math.min(window.screenVars.vwScreen / screenMaxRatio * 100, screenMaxHeight);
        }
        window.screenVars.fontSize = window.screenVars.vhScreen;

        node.style.width = containerWidth + 'px';
        node.style.height = containerHeight + 'px';

        if (window.screenVars.landscape) {
            node.style.rotate = '0deg';
            node.style.top = `50%`;
            node.style.left = `50%`;
        } else {
            node.style.rotate = '90deg';
            node.style.top = `calc(50% + ${(containerWidth - containerHeight) / 2}px)`;
            node.style.left = `calc(50% - ${(containerWidth + containerHeight) / 2}px)`;
        }
    }

    replaceCssFile(layoutCssSelector, layoutCssFilename+'.zoom');

    return window.screenVars.zoomRatio;
}

function resizeScreen(node) {
    setScreenSizeUnit();

    const zoomRatio = (zoomAdjust)?resizeScaleByZoomRatio(node):resizeScaleByFontSize(node);

    document.documentElement.style.setProperty('--vh-font', `${window.screenVars.fontSize}px`);
    document.documentElement.style.setProperty('--zoom-ratio', `${window.screenVars.zoomRatio}px`);
    document.documentElement.style.setProperty('--rotate-deg', `${node.style.rotate}`);

    if(node.style.rotate === '' || parseInt(node.style.rotate.replace('deg', '')) === 0){
        document.body.classList.remove('rotate');
    }else{
        document.body.classList.add('rotate');
    }

    setZoomScale(node, zoomRatio);
}

function setZoomScale(node, ratio)
{
    node.style.zoom = ratio;
    if(node.style.transform !== '') node.style.transform = node.style.transform;

	window.dispatchEvent(
		new CustomEvent('ResizeScreen', {
			bubbles : false,
			cancelable : true,
			composed : false,
		}),
	);
}

function setLoadHTMLSize(selector) {
    setUserAgentPropeties();
    if(designWidth || designHeight){
        resizeScreen(document.querySelector(selector));
    }

    if(window.resizeCallback !== undefined) resizeCallback();

	window.dispatchEvent(
		new CustomEvent('LoadHTMLSize', {
			bubbles : false,
			cancelable : true,
			composed : false,
		}),
	);
}

$(function(){
    setLoadHTMLSize(rootContainer);
    window.onresize = () => { setLoadHTMLSize(rootContainer); }
})
