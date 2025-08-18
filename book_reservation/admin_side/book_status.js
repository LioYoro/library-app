function updateStatus(bookId, newStatus) {
    const row = document.getElementById(`row-${bookId}`);
    row.classList.add("loading");

    fetch("", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `book_id=${bookId}&new_status=${newStatus}`
    })
    .then(async res => {
        const text = await res.text();
        try {
            return JSON.parse(text);
        } catch (e) {
            throw new Error("Invalid JSON response: " + text);
        }
    })
    .then(data => {
        row.classList.remove("loading");
        if (data.success) {
            document.getElementById(`status-${bookId}`).textContent = data.new_status;
        } else {
            alert("Failed: " + (data.error || "Unknown error"));
        }
    })
    .catch(err => {
        row.classList.remove("loading");
        console.error(err);
        alert("Error updating status: " + err.message);
    });
}

function filterBooks() {
    const input = document.getElementById("bookSearch");
    const filter = input.value.toLowerCase();
    const table = document.getElementById("booksTable");
    const trs = table.getElementsByTagName("tr");

    for (let i = 1; i < trs.length; i++) { // skip the header row
        const titleTd = trs[i].getElementsByClassName("book-title")[0];
        if (titleTd) {
            const txtValue = titleTd.textContent || titleTd.innerText;
            trs[i].style.display = txtValue.toLowerCase().includes(filter) ? "" : "none";
        }
    }
}
