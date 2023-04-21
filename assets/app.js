// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.css';

// start the Stimulus application
// import './bootstrap';

// JQuery
import $ from 'jquery';

// Toastify
import ToastifyEs from "toastify-js/src/toastify-es";


for (let type in alerts) {
    for (let i = 0; i < alerts[type].length; i++) {
        ToastifyEs({
            text: alerts[type][i],
            className: `alert alert-${type}`,
            duration: 10_000,
            gravity: 'bottom',
            position: 'right',
            stopOnFocus: true,
            // close: true
        }).showToast()
    }
}

function hearts() {
    $.each($(".header-logo"), function () {
        const heartCount = ($(this).width() / 50) * 5;
        for (let i = 0; i <= heartCount; i++) {
            let size = ($.rnd(60, 120) / 10);
            $(this).append('<span class="particle" style="top:' + $.rnd(20, 80) + '%; left:' + $.rnd(0, 95) + '%;width:' + size + 'px; height:' + size + 'px;animation-delay: ' + ($.rnd(0, 30) / 10) + 's;"></span>');
        }
    });
}

$.rnd = function (m, n) {
    m = parseInt(m);
    n = parseInt(n);
    return Math.floor(Math.random() * (n - m + 1)) + m;
}

hearts();