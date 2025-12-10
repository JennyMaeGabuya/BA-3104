import { generateSpots } from "./reservation";


if(document.getElementById("cancelReservationBtn")) {
    document.getElementById("cancelReservationBtn").addEventListener("click", async () => {
    if (!confirm("Are you sure you want to cancel your reservation?")) return;

    try {
      const res = await fetch("http://localhost/ParkEase/BA-3104/backend/public/delete-reservation", {
        method: "DELETE",
        credentials: "include"
      });

      const data = await res.json();
      console.log("Cancel response:", data);

      if (data.status === "success") {
        alert("Your reservation has been canceled.");

        // Reset global reservation
        currentReservation = null;

        // Re-enable form
        document.querySelectorAll("#reservationForm input, #reservationForm select")
          .forEach(el => el.disabled = false);

        // Reset preview button
        previewBtn.disabled = false;
        previewBtn.textContent = "Preview Reservation";
        previewBtn.classList.remove("opacity-50", "cursor-not-allowed");

        // Hide cancel button
        document.getElementById("cancelReservationBtn").classList.add("hidden");

        // Clear summary panel
        summaryBody.innerHTML = "";
        downloadBtn.disabled = true;
        downloadBtn.classList.add("opacity-50", "cursor-not-allowed");

        // Refresh spots (to free the canceled spot)
        generateSpots();

      } else {
        alert("Failed to cancel reservation.");
      }

    } catch (err) {
      console.error("Cancel error:", err);
      alert("Server error while canceling reservation.");
    }
  });
}


