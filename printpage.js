
const { jsPDF } = window.jspdf;



function createWhiteExternalSVGCB(url, callback) { //not used
    fetch(url)
        .then(response => response.text())
        .then(svgText => {
            const parser = new DOMParser();
            const svgDoc = parser.parseFromString(svgText, "image/svg+xml");
            const svgElement = svgDoc.querySelector("svg");
            const whiteSVG = setWhiteInlineSVG(svgElement);
            callback(whiteSVG);
        })
        .catch(console.error);
}

function createWhiteExternalSVG(url) {
    return fetch(url)
        .then(response => response.text())
        .then(svgText => {
            const parser = new DOMParser();
            const svgDoc = parser.parseFromString(svgText, "image/svg+xml");
            const svgElement = svgDoc.querySelector("svg");
            return setWhiteInlineSVG(svgElement);
        });
}


function setWhiteInlineSVG(svgElement) {
    const clone = svgElement.cloneNode(true); // Clone the SVG
    clone.querySelectorAll("path").forEach(el => {
        if (el.hasAttribute("style")) {
            el.setAttribute("style", "");
        }
        if (el.hasAttribute("fill")) {
            el.setAttribute("fill", `#FFFFFF`);
        } else {
            el.setAttribute("fill", `#FFFFFF`);
        }
        if (el.hasAttribute("pagecolor")) {
            el.setAttribute("pagecolor", `#FFFFFF`);
        }
        if (el.hasAttribute("stroke")) {
            el.setAttribute("stroke", `#FFFFFF`);
        }
    });
    return clone;
}

function processAndScaleImages(images) {
    const promises = Array.from(images).map(image => {
        const url = image.src;
        return createWhiteExternalSVG(url).then(invertedSVG => {
            image.parentNode.replaceChild(invertedSVG, image);
            invertedSVG.classList = image.classList;
            return invertedSVG; // Return the modified SVG for further processing
        });
    });
    // Once all images are inverted and replaced, scale them
    Promise.all(promises).then(invertedImages => {
        shrinkImagesForPrint(invertedImages); // Scale the replaced SVGs
    });
}

// Usage
//const boxImages = document.querySelectorAll(".images img");
//processAndScaleImages(boxImages);


function generatePDF() {
    const element = document.getElementById("page-container"); // The content to print

    const options = {
        margin: [10, 10], // Top, left, bottom, right margins
        filename: `${document.getElementById("listName").textContent}-${document.querySelector("title").textContent}.pdf`,
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: {
            scale: 2, // Adjust scale for better resolution
            useCORS: true, // Handle cross-origin images
            allowTaint: true,
        },
        jsPDF: { unit: 'pt', format: 'a4', orientation: 'portrait' },
    };

    html2pdf()
        .from(element)
        .set(options)
        .save()
        .catch(error => {
            console.error("Error generating PDF:", error);
        });
        return "Done";
}


document.getElementById("generatePdfButton").addEventListener("click", function () {
    pdfPrint1();
    /*
    document.body.classList.add("print-mode");

    // Once all images are inverted and replaced, scale them
    Promise.all(generatePDF()).then(() => {
        document.body.classList.remove("print-mode");// Scale the replaced SVGs
    });
    */
    
});

function pdfPrint1() {
document.getElementById("generatePdfButton").addEventListener("click", function () {
    // The DOM element you want to convert to PDF
        // Add print-mode class to apply @media print styles
    document.body.classList.add("print-mode");

    const elementToPrint = document.getElementById("page-container"); // Change "content" to your DOM element ID
    
    // Configure html2canvas
    const options = {
        scale: 2, // High resolution
        useCORS: true, // Cross-origin support
        allowTaint: true, // Allow cross-origin images
        windowWidth: elementToPrint.scrollWidth,
        windowHeight: elementToPrint.scrollHeight,
    };

    // Use html2canvas to render the DOM element to a canvas
    html2canvas(elementToPrint, options).then(canvas => {

        // Create a new jsPDF instance
        const pdf = new jsPDF({
            orientation: "portrait",
            unit: "mm",
            format: "a4",
        });

        // Calculate the width and height for A4
        const pdfWidth = pdf.internal.pageSize.getWidth();
        const pdfHeight = pdf.internal.pageSize.getHeight();

        const canvasWidth = canvas.width;
        const canvasHeight = canvas.height;

        const imgWidth = pdfWidth - 20; // Subtract for margins
        const imgHeight = (canvasHeight * imgWidth) / canvasWidth;

        // Prepare for slicing the canvas into multiple pages
        const pageHeight = (canvasWidth / pdfWidth) * pdfHeight; // Height of a PDF page in canvas units
        let yOffset = 0;

        while (yOffset < canvasHeight) {
            const pageCanvas = document.createElement("canvas");
            const ctx = pageCanvas.getContext("2d");

            pageCanvas.width = canvasWidth;
            pageCanvas.height = Math.min(pdfHeight * (canvasWidth / pdfWidth), canvasHeight - yOffset);

            ctx.drawImage(
                canvas,
                0,
                yOffset,
                canvasWidth,
                pageCanvas.height,
                0,
                0,
                canvasWidth,
                pageCanvas.height
            );

            const pageData = pageCanvas.toDataURL("image/png");
            pdf.addImage(pageData, "PNG", 10, 10, imgWidth, (imgWidth * pageCanvas.height) / canvasWidth);

            yOffset += pageCanvas.height;
            if (yOffset < canvasHeight) pdf.addPage();
        }
        // Save the PDF
        pdf.save(`${document.getElementById("listName").textContent}-${document.querySelector("title").textContent}.pdf`);
    }).catch(error => {
        console.error("Error generating PDF:", error);
        alert("Failed to generate PDF.");
    }).finally(() => {
        // Remove the print-mode class after PDF generation
        document.body.classList.remove("print-mode");
    });
});
}