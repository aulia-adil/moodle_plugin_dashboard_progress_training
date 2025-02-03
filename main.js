function toggleMoreContent(event) {
    event.preventDefault();
    var moreContent = document.getElementById('more-content');
    var icon = document.querySelector('.triangle i');
    var iconSpan = document.querySelector('.triangle span');
    if (moreContent.style.display === 'none') {
        moreContent.style.display = 'block';
        icon.className = 'fa-solid fa-angle-down';
        iconSpan.style.paddingTop = '2px';
    } else {
        moreContent.style.display = 'none';
        iconSpan.style.paddingTop = '0.5px';
        icon.className = 'fa-solid fa-angle-right';
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
function updateGradientVisibilityMonthsScroll() {
    var container = document.getElementsByClassName('months-container')[0];
    var gradientLeft = document.querySelector('.gradient-left');
    var gradientRight = document.querySelector('.gradient-right');

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

function updateGradientVisibilityTableOverview() {
    var courseTableContainer = document.getElementsByClassName('table-container')[0];
    var tableContainerAfterGradient = document.querySelector('.gradient-table-container-top');
    var tableContainerBeforeGradient = document.querySelector('.gradient-table-container-bottom');
    console.log("courseTableContainer.scrollTop = " + courseTableContainer.scrollTop);
    console.log(tableContainerAfterGradient);

    if (courseTableContainer.scrollTop === 0) {
        tableContainerAfterGradient.style.opacity = '0';
    } else {
        tableContainerAfterGradient.style.opacity = '1';
    }

    if (courseTableContainer.scrollTop + courseTableContainer.offsetHeight >= courseTableContainer.scrollHeight) {
        tableContainerBeforeGradient.style.opacity = '0';
    } else {
        tableContainerBeforeGradient.style.opacity = '1';
    }
}

document.getElementById('triangle').addEventListener('click', toggleMoreContent);
document.getElementById('show-more-link').addEventListener('click', toggleMoreContent);
var test = document.getElementsByClassName('months-container')[0];

test.addEventListener('scroll', updateGradientVisibilityMonthsScroll);
document.getElementsByClassName('table-container')[0].addEventListener('scroll', updateGradientVisibilityTableOverview);

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