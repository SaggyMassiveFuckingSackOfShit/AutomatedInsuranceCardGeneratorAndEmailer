document.addEventListener("DOMContentLoaded", () => {
    const elements = {
        fileInput: document.getElementById("excelFile"),
        fileNameDisplay: document.getElementById("fileName"),
        responseMessage: document.getElementById("responseMessage"),
        uploadForm: document.getElementById("uploadForm")
    };

    elements.fileInput.addEventListener("change", () => {
        elements.fileNameDisplay.textContent = elements.fileInput.files[0]?.name || "No file selected";
    });

    elements.uploadForm.addEventListener("submit", event => {
        event.preventDefault();
        if (!elements.fileInput.files.length) {
            alert("Please upload an Excel file first.");
            return;
        }

        const formData = new FormData();
        formData.append("excelFile", elements.fileInput.files[0]);
        formData.append("generate", "1");

        elements.responseMessage.innerHTML = "Processing...";
        
        fetch("generator.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.text())
        .then(text => {
            elements.responseMessage.innerHTML = text;
        })
        .catch(error => {
            console.error('Fetch error:', error);
            elements.responseMessage.innerHTML = `<p style="color: red;">Error: ${error.message}</p>`;
        });
    });
});
