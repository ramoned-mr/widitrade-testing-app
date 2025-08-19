document.addEventListener("DOMContentLoaded", () => {
    const body = document.querySelector("body");
    body.setAttribute("style", "height:" + window.innerHeight + "px!important;");
    window.addEventListener('resize', function () {
        body.setAttribute("style", "height:" + window.innerHeight + "px!important;");
    });
}, false);