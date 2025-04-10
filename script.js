document.addEventListener("DOMContentLoaded", function () {
    const fileInput = document.getElementById("excelFile");
    const fileNameDisplay = document.getElementById("fileName");
    const responseMessage = document.getElementById("responseMessage");
    const uploadForm = document.getElementById("uploadForm");

    // Display the selected file name
    fileInput.addEventListener("change", function () {
        if (fileInput.files.length > 0) {
            fileNameDisplay.textContent = fileInput.files[0].name;
        } else {
            fileNameDisplay.textContent = "No file selected";
        }
    });

    // AJAX Form Submission
    uploadForm.addEventListener("submit", function (event) {
        event.preventDefault();

        if (!fileInput.files.length) {
            alert("Please upload an Excel file first.");
            return;
        }

        const formData = new FormData();
        formData.append("excelFile", fileInput.files[0]);
        formData.append("generate", "1"); // This simulates the submit button name


        fetch("generator.php", {
            method: "POST",
            body: formData,
        })
        .then(response => response.text())
        .then(data => {
            clearInterval(progressInterval);
            responseMessage.innerHTML = `<p style="color: red;">${data}</p>`;
        })
        .catch(error => {
            responseMessage.innerHTML = `<p style="color: red;">Error: ${error.message}</p>`;
        });
    });

    let progressInterval;
});
