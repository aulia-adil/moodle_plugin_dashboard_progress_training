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
var courseTableContainer = document.getElementsByClassName('table-container')[0];
var tableContainerAfterGradient = document.querySelector('.gradient-table-container-top');
var tableContainerBeforeGradient = document.querySelector('.gradient-table-container-bottom');
// console.log("courseTableContainer.scrollTop = " + courseTableContainer.scrollTop);
// console.log(tableContainerAfterGradient);

if (courseTableContainer.scrollTop === 0) {
    tableContainerAfterGradient.style.opacity = '0';
} else {
    tableContainerAfterGradient.style.opacity = '1';
}
function updateGradientVisibilityTableOverview() {
    var courseTableContainer = document.getElementsByClassName('table-container')[0];
    var tableContainerAfterGradient = document.querySelector('.gradient-table-container-top');
    var tableContainerBeforeGradient = document.querySelector('.gradient-table-container-bottom');
    // console.log("courseTableContainer.scrollTop = " + courseTableContainer.scrollTop);
    // console.log(tableContainerAfterGradient);

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
    const descriptions = document.querySelectorAll('.description');
    descriptions.forEach(description => {
        const fullText = description.getAttribute('data-full-text');
        let maxLength;

        if (window.innerWidth < 576) {
            maxLength = 30; // Mobile devices
        } else if (window.innerWidth < 768) {
            maxLength = 40; // Tablets
        } else {
            maxLength = 50; // Desktops
        }

        if (fullText.length > maxLength) {
            description.textContent = fullText.substring(0, maxLength) + '...';
        } else {
            description.textContent = fullText;
        }
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const buttons = document.querySelectorAll('.month-btn');
    const rows = document.querySelectorAll('#course-overview-table tbody tr');
    console.log(rows);

    buttons.forEach(button => {
        button.addEventListener('click', function() {
            const month = this.getAttribute('data-month');
            button.classList.add('persistent-focus');
            buttons.forEach(b => {
                if (b !== button) {
                    b.classList.remove('persistent-focus');
                }
            });

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

document.addEventListener('DOMContentLoaded', function() {
    const descriptions = document.querySelectorAll('.description');
    const overlay = document.getElementById('overlay');
    const overlayActivityName = document.getElementById('overlay-activity-name');
    const overlayActivityTime = document.getElementById('overlay-activity-time');
    const overlayActivityDuration = document.getElementById('overlay-activity-duration');
    const overlayActivityType = document.getElementById('overlay-activity-type');
    const overlayActivityLink = document.getElementById('overlay-activity-link');
    const closeBtn = document.querySelector('.close-btn');
    const newTabIcon = document.getElementById('new-tab-icon');

    descriptions.forEach(description => {
        description.addEventListener('click', function(event) {
            event.preventDefault();
            overlayActivityName.textContent = description.getAttribute('data-activity-name');
            overlayActivityTime.textContent = description.getAttribute('data-activity-time');
            overlayActivityDuration.textContent = description.getAttribute('data-activity-duration');
            overlayActivityLink.href = description.getAttribute('data-activity-link');
            overlayActivityType.textContent = description.getAttribute('data-activity-type');
            overlay.style.display = 'flex';
        });
    });

    newTabIcon.addEventListener('click', function(event) {
        event.preventDefault();
        window.open(overlayActivityLink.href, '_blank');
    });

    closeBtn.addEventListener('click', function() {
        overlay.style.display = 'none';
    });

    window.addEventListener('click', function(event) {
        // console.log("event.target = " + event.target);
        if (event.target === overlay) {
            // console.log("overlayContent.contains(event.target) = " + overlayContent.contains(event.target));
            overlay.style.display = 'none';
        }
    });
});