window.onerror = function(message, source, lineno, colno, error) {
    if (typeof message === "string" &&
        (message.indexOf("closest is not a function") > -1 ||
            message.indexOf("Cannot read properties of null") > -1 ||
            message.indexOf("Cannot read properties of undefined (reading 'trim')") > -1)) {
        return true;
    }
    return false; // 다른 오류는 그대로 처리
};

// 리스너를 추적하는 코드
(function() {
    var eventListeners = [];

    /**
     * [Violation] Added non-passive event listener to a scroll-blocking <some> event. Consider marking event handler as 'passive' to make the page more responsive. See <URL>
     * TODO 위 Violation 관련, scroll event에 따라 passive:true 를 적용하거나 아니거나를 분기처리 해야 함.
     * @type {(type: string, callback: (EventListenerOrEventListenerObject | null), options?: (AddEventListenerOptions | boolean)) => void}
     */
    var origAddEventListener = EventTarget.prototype.addEventListener;
    EventTarget.prototype.addEventListener = function(type, listener, options) {
        if(['DOMNodeInserted'].includes(type)) return;
        eventListeners.push({target: this, type: type, listener: listener, options: options});
        origAddEventListener.call(this, type, listener, {
            ...options,
            // passive: ['touchstart', 'touchend', 'touchmove', 'scroll', 'mousedown', 'mousemove', 'mouseup', 'pointerdown', 'pointermove', 'pointerup'].includes(type)?true:false,
        });
    };

    var origRemoveEventListener = EventTarget.prototype.removeEventListener;
    EventTarget.prototype.removeEventListener = function(type, listener, options) {
        eventListeners = eventListeners.filter(
            event => event.target !== this || event.type !== type || event.listener !== listener || event.options !== options
        );
        origRemoveEventListener.call(this, type, listener, options);
    };

    window.getEventListeners = function(element) {
        return eventListeners.filter(event => event.target === element);
    };
})();
