import $ from 'jquery';

export function squares() {
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

export default {squares};