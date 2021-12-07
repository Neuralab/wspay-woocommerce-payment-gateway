let script = document.createElement('script');
script.src = wspayClientScript.iframeResizerJs;
script.async = false;
script.defer = false;
script.onload = function () {
    iFrameResize({checkOrigin: false}, wspayClientScript.wspayIframeId);
};
document.head.appendChild(script);
