// reservations.js
let currentActionId = 0;
let currentActionType = "";

function showPrompt(id, action) {
  currentActionId = id;
  currentActionType = action;

  const buttons = document.getElementById(`buttons-${id}`);
  if (buttons) buttons.classList.add("hidden");

  const promptBox = document.getElementById(`prompt-${id}`);
  if (promptBox) {
    const actionText = document.getElementById(`prompt-action-text-${id}`);
    if (actionText) actionText.textContent = action.charAt(0).toUpperCase() + action.slice(1);
    promptBox.style.display = "block";
  }
}

function hidePrompt(id) {
  const promptBox = document.getElementById(`prompt-${id}`);
  if (promptBox) promptBox.style.display = "none";

  const buttons = document.getElementById(`buttons-${id}`);
  if (buttons) buttons.classList.remove("hidden");

  currentActionId = 0;
  currentActionType = "";
}

function executeAction(id) {
  const container = document.getElementById(`container-${id}`);
  if (container) container.classList.add("loading");

  fetch("", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: `reservation_id=${currentActionId}&action=${currentActionType}`,
  })
    .then(async (res) => {
      const text = await res.text();
      try {
        return JSON.parse(text);
      } catch (e) {
        throw new Error("Invalid JSON response: " + text);
      }
    })
    .then((data) => {
      if (container) container.classList.remove("loading");

      if (data.success) {
        const row = document.getElementById(`row-${id}`);
        if (!row) return;

        // Update status cell
        const statusCell = document.getElementById(`status-${id}`);
        if (statusCell) statusCell.innerHTML = `<span class="status-${data.new_status}">${data.new_status}</span>`;

        // Update done column
        const doneCell = row.querySelector("td:nth-child(7)");
        if (doneCell) doneCell.textContent = data.done ? "Yes" : "No";

        // Show result message if exists
        const result = document.getElementById(`result-${id}`);
        const resultText = document.getElementById(`result-text-${id}`);
        if (result && resultText) {
          let resultClass = "", resultMessage = "";
          switch (currentActionType) {
            case "confirm":
              resultClass = "result-confirmed";
              resultMessage = "✓ Confirmed";
              break;
            case "cancel":
              resultClass = "result-cancelled";
              resultMessage = "✗ Cancelled";
              break;
            case "done":
              resultClass = "result-done";
              resultMessage = "✓ Completed";
              break;
          }
          result.className = `action-result ${resultClass}`;
          resultText.textContent = resultMessage;
          result.style.display = "block";
        }

        // Hide prompt
        hidePrompt(id);

        // Update action buttons or replace with "No actions available"
        const buttons = document.getElementById(`buttons-${id}`);
        const actionsCell = row.querySelector("td:nth-child(10)");
        if (data.done === 1 || data.new_status === "cancelled") {
          if (buttons) buttons.style.display = "none";
          if (actionsCell) actionsCell.innerHTML = "<em>No actions available</em>";
        } else if (buttons) {
          buttons.classList.remove("hidden");
        }

        currentActionId = 0;
        currentActionType = "";
      } else {
        alert("Failed: " + (data.error || "Unknown error"));
        hidePrompt(id);
      }
    })
    .catch((err) => {
      if (container) container.classList.remove("loading");
      console.error("Reservation Error:", err);
      alert("Error: " + (err.message || "An unknown error occurred."));
      hidePrompt(id);
    });
}
