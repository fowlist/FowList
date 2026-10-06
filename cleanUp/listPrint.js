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



                // Final safety check
                if (!isNaN(width) && !isNaN(height) && width > 0 && height > 0) {
                    image.dataset.originalWidth = width;
                    image.dataset.originalHeight = height;

                    image.setAttribute("width", (width * 0.55) + "px");
                    image.setAttribute("height", (height * 0.55) + "px");
                } else {
                    console.warn("Could not determine SVG size, skipping:", image);
                }

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
    const qrDiv = document.getElementById("qrcode");
    const url = qrDiv.dataset.url;  // read data-url
    new QRCode(qrDiv, {
        correctLevel : QRCode.CorrectLevel.L,
        text: url,
        width: 256,
        height: 256
    });

  // Collect all icon promises first
  const iconPromises = Array.from(document.querySelectorAll(".icon")).map(async el => {
    const url = el.dataset.icon;
    const svg = await fetch(url).then(r => r.text());
    const parser = new DOMParser();
    const doc = parser.parseFromString(svg, "image/svg+xml");
    const svgEl = doc.querySelector("svg");

  // Calculate dimensions FIRST
    let width = parseFloat(svgEl.getAttribute("width")) * 0.58;
    let height = parseFloat(svgEl.getAttribute("height")) * 0.58;
    if (!width || !height) {
      const vb = svgEl.getAttribute("viewBox");
      if (vb) {
        const parts = vb.split(" ");
        width = parts[2];
        height = parts[3];
      }
    }

  svgEl.style.fill = "currentColor";
    svgEl.querySelectorAll("path, g, rect, circle, polygon").forEach(child => {
    if (child.getAttribute("fill") && child.getAttribute("fill") !== "none") {
        child.removeAttribute("fill");
    }
    child.style.fill = ""; // clear any inline fill styles too
    });

  svgEl.querySelectorAll("[fill]").forEach(child => {
    if (child.getAttribute("fill") !== "none") {
      child.removeAttribute("fill");
    }
  });

  if (width && height) {
    svgEl.setAttribute("width", width + "mm");
    svgEl.setAttribute("height", height + "mm");
  }

  svgEl.classList.add("inline-icon");
  if (el.classList.contains("icon-white")) {
    svgEl.classList.add("inline-icon-white");
  }

  el.style.display = "inline-block";
  el.style.overflow = "visible"; // change this from hidden to visible
  el.style.verticalAlign = "middle";
  el.appendChild(svgEl);

  el.style.display = "inline-block";


  el.style.removeProperty("mask-image");
  el.style.removeProperty("-webkit-mask-image");
  el.style.removeProperty("background-color");


  });

  // Wait for ALL icons to finish, then init Packery
  Promise.all(iconPromises).then(() => {
    shrinkImagesForPrint(document.querySelectorAll('img'));

    const boxPoints = document.querySelectorAll(".box .Points, .optional-item .Points");
    boxPoints.forEach(element => {
        element.addEventListener("click", () => togglePoints(element));        
    });

    const dragToggleSwitch = document.getElementById("dragToggleSwitch");      
    const draggies = new Map();

    $('.grid').each(function(i, gridElement) {
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

function subtractKeywordPoints() {
    // Get the reserves points element
    const reservesPoints = document.getElementById('reservesPoints');
    const totalPoints = document.getElementById('totalPoints');
    let totalPointsValue = parseInt(totalPoints.textContent);
    
    // Keywords to subtract
    const subtractKeywords = [];
    const poolKeywords = ["Our Land", "Sperrverband","Local Militia", "Already Here"]; // add other pool keywords here

    let totalSubtract = 0;
    let totalPool = 0;
    
    subtractKeywords.forEach(keyword => {
        // Find all Points divs with the current keyword
        const keywordPoints = document.querySelectorAll(
            `.Points[data-platooninfo*="${keyword}"]`
        );
        console.log(`Found ${keywordPoints.length} points for keyword: ${keyword}`);
        keywordPoints.forEach(element => {
            // Extract the points value
            const pointsText = element.querySelector('div').textContent.trim();
            const points = parseInt(pointsText);
            
            if (!isNaN(points)) {
                totalSubtract += points;
            }
            
            // Remove click event listener to disable interaction
            element.style.pointerEvents = 'none';
            element.classList.add('disabled');
        });
    });
        // process poolKeywords: sum AND disable (as requested)
    poolKeywords.forEach(keyword => {
        const keywordPoints = document.querySelectorAll(`.Points[data-platooninfo*="${keyword}"]`);
        //console.log(`Found ${keywordPoints.length} pool points for keyword: ${keyword}`);
        keywordPoints.forEach(element => {
            const inner = element.querySelector('div') || element;
            const points = parseInt((inner.textContent||"").trim()) || 0;
            if (points) totalPool += points;
            element.style.pointerEvents = 'none';
            element.classList.add('disabled', 'pool-included');
        });
    });
    


    // Calculate reserves as 40% of (total - subtractKeywords)
    const reservesValue = Math.round((totalPointsValue - totalSubtract) * 0.4) - totalPool;
    reservesPoints.textContent = reservesValue + ' points';
    totalPoints.textContent = totalPoints.textContent

    // Create or update an "Excluded" reserves element that shows the totalSubtract
    const reservesContainer = reservesPoints ? reservesPoints.closest('.Points') : null;
    const outerClone = reservesContainer.cloneNode(true);
    const innerReserves = outerClone.querySelector('.reservesPoints');
    if (innerReserves&&totalPool+totalSubtract>0) {
        outerClone.removeChild(innerReserves);
        let excludedEl = document.getElementById('reservesExcluded');
        const text = `Excluded: ${totalSubtract+totalPool} points`;
        if (excludedEl) {
            excludedEl.textContent = text;
        } else {
            excludedEl = document.createElement('div');
            excludedEl.id = 'reservesExcluded';
            excludedEl.className = 'reservesExcluded Points1';
            excludedEl.textContent = text;
            // Insert after the reserves container
            reservesContainer.insertAdjacentElement('afterend', outerClone);
            outerClone.appendChild(excludedEl);
        }
    }
}

// Call on page load
window.addEventListener('load', function() {
    subtractKeywordPoints();
});