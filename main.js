function toggleMoreContent(event) {
    event.preventDefault();
    var moreContent = document.getElementById('more-content');
    var icon = document.querySelector('.triangle i');
    if (moreContent.style.display === 'none') {
        moreContent.style.display = 'block';
        icon.className = 'fa-solid fa-angle-up';
    } else {
        moreContent.style.display = 'none';
        icon.className = 'fa-solid fa-angle-down';
    }
}
    var gradientLeft = document.querySelector('.gradient-left');
    var gradientRight = document.querySelector('.gradient-right');
    var container = document.getElementsByClassName('months-container')[0];

    if (container.scrollLeft === 0) {
        gradientLeft.style.opacity = '0';
    } else {
        gradientLeft.style.opacity = '1';
    }
function updateGradientVisibility() {
    var container = document.getElementsByClassName('months-container')[0];
    var gradientLeft = document.querySelector('.gradient-left');
    var gradientRight = document.querySelector('.gradient-right');
    console.log
    // console.log('TESTING' + container.scrollLeft);
    // console.log(gradientLeft);

    if (container.scrollLeft === 0) {
        gradientLeft.style.opacity = '0';
    } else {
        gradientLeft.style.opacity = '1';
    }

    if (container.scrollLeft + container.offsetWidth >= container.scrollWidth) {
        gradientRight.style.opacity = '0';
    } else {
        gradientRight.style.opacity = '1';
    }
}
document.getElementById('triangle').addEventListener('click', toggleMoreContent);
document.getElementById('show-more-link').addEventListener('click', toggleMoreContent);
var test = document.getElementsByClassName('months-container')[0];
// console.log(test);
// console.log('TESTING 123' + test.scrollLeft);
test.addEventListener('scroll', updateGradientVisibility);

document.addEventListener('DOMContentLoaded', function() {
    const buttons = document.querySelectorAll('.month-btn');
    const rows = document.querySelectorAll('#course-overview-table tr');
    console.log(rows);

    buttons.forEach(button => {
        button.addEventListener('click', function() {
            const month = this.getAttribute('data-month');
            // console.log(month);

            rows.forEach(row => {
                console.log("row.getAttribute('data-month') = " + row.getAttribute('data-month'));
                if (month === 'all' || row.getAttribute('data-month') === month) {
                    // console.log("DISPLAY = kosong");
                    row.style.display = '';
                } else {
                    // console.log("DISPLAY = none");
                    row.style.display = 'none';
                }
            });
        });
    });
});