document.addEventListener("DOMContentLoaded", function () {
    const collapsibleHeader = document.querySelectorAll(".collapsible");

    collapsibleHeader.forEach(header => {    
        header.addEventListener("click", () => {
            header.classList.toggle("active");
            const content = header.nextElementSibling;
            if (content.style.display === "none") {
                content.style.display = "inline-block";
            } else {
                content.style.display = "none";
            }
        });
    });
});

function shrinkImagesForPrint(images) {
    images.forEach(image => {
        const isSVG = image.nodeName.toLowerCase() === "svg";

        // Store original dimensions as dataset attributes for later restoration
        if (typeof image.dataset.originalWidth == 'undefined') {
            if (isSVG) {              
                const widthAttr = image.getAttribute("width");
                const heightAttr = image.getAttribute("height");
                const width = parseFloat(widthAttr); 
                const height = parseFloat(heightAttr);

                // Store original dimensions
                image.dataset.originalWidth = width;
                image.dataset.originalHeight = height;
                const scaledWidth = width * 0.55;
                const scaledHeight = height * 0.55;

                image.setAttribute("width", scaledWidth+"px");
                image.setAttribute("height", scaledHeight+"px");
                //image.removeAttribute("viewBox");
                //image.setAttribute("viewBox", `0 0 ${width} ${height}`);
            }else {
                // For <img> elements
                image.dataset.originalWidth = image.offsetWidth;
                image.dataset.originalHeight = image.offsetHeight;

                // Scale dimensions
                image.style.width = image.dataset.originalWidth * 0.55 + "px";
                image.style.height = image.dataset.originalHeight * 0.55 + "px";
            }
        } 
    });
}

window.addEventListener('load', function() {
    shrinkImagesForPrint(document.querySelectorAll('img'));
    const boxPoints = document.querySelectorAll(".box .Points, .optional-item .Points");
       
    boxPoints.forEach(element => {
        element.addEventListener("click", () => togglePoints(element));        
    });
    const dragToggleSwitch = document.getElementById("dragToggleSwitch");      
    // Loop through each grid instance to initialize Packery individually
    const draggies = new Map(); // Map: gridElement -> Map(gridItem -> draggie)
    $('.grid').each(function(i, gridElement) {
        // Initialize Packery for the current grid instance
        var $grid = $(gridElement).packery({
            itemSelector: '.box',
            columnWidth: 240
        });
        
        dragToggleSwitch.addEventListener("change", function (params) {
            if (dragToggleSwitch.checked) {
                // Make all .box elements within this grid draggable
                $grid.find('.box').each(function(j, gridItem) {
                if (!draggies.has(gridItem)) { // Prevent duplicate Draggabilly instances
                    var draggie = new Draggabilly(gridItem);

                    // Prevent dragging if the click is on the .Points div
                    $(gridItem).on('pointerdown', function(event) { 
                        if ($(event.target).closest('.Points').length > 0) {
                            draggie.disable(); // Disable drag if target is .Points
                        } else {
                            draggie.enable(); // Enable drag elsewhere
                        }
                    });

                    // Bind drag events to Packery for this specific grid
                    $grid.packery('bindDraggabillyEvents', draggie);

                    // Store draggie instance
                    draggies.set(gridItem, draggie);
                }
                });
            } else {
                $grid.find('.box').each(function(j, gridItem) {
                    const draggie = draggies.get(gridItem);
                    $(gridItem).on('pointerdown', function(event) {
                        if (draggie) {
                            draggie.disable();
                            draggie.unbindHandles();
                        }
                    });
                });

            }
        });

    });
});

function togglePoints(element) {

    // Get the total points div
    var totalPoints = document.getElementById('reservesPoints');
    // Get the current points
    var currentPoints = parseInt(totalPoints.textContent);
    // Get the points in the clicked div
    var pointsInDiv = parseInt(element.textContent.trim());
    if (!totalPoints.classList.contains("changed")) {
        totalPoints.classList.add("changed");
    }

    // If the points in the div are not a number, return
    if (isNaN(pointsInDiv)) {
        return;
    }
    // Toggle the selected class to change color
    element.classList.toggle('selected');
    element.classList.toggle('changed');


    // If the div is selected (blue), add its points to the total, else subtract its points from the total
    if (element.classList.contains('selected')) {
        if (isMobile()) {
            element.querySelector("div").textContent = pointsInDiv;
        }
        totalPoints.textContent = currentPoints - pointsInDiv + ' Points';
    } else {
        if (isMobile()) {
            element.querySelector("div").textContent = pointsInDiv + ' Points';
        }
        totalPoints.textContent = currentPoints + pointsInDiv + ' Points';
    }
}

function isMobile() {
    var match = window.matchMedia || window.msMatchMedia;
    if(match) {
        var mq = match("(pointer:coarse)");
        return mq.matches;
    }
    return false;
}