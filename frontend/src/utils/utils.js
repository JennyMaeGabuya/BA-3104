
export function showModal(message) {
    // Create the modal container
    const modal = document.createElement("div");
    modal.classList.add("modal");
    Object.assign(modal.style, { // shortcut to modal.style = this is an object
        position: "fixed",
        top: "0",
        left: "0",
        width: "100%",
        height: "100%",
        backgroundColor: "rgba(0,0,0,0.5)",
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        zIndex: "9999",
        opacity: "0",           // start invisible
        transition: "opacity 0.3s ease"
    });

    // Modal content
    const content = document.createElement("div");
    Object.assign(content.style, {
        background: "#fff",
        padding: "20px 30px",
        borderRadius: "8px",
        textAlign: "center",
        transform: "translateY(-20px)", // start slightly up
        transition: "all 0.3s ease"
    });
    content.innerHTML = `<p>${message}</p><button id="closeModal">OK</button>`;

    modal.appendChild(content);
    document.body.appendChild(modal);

    // Trigger the fade-in + slide-down
    requestAnimationFrame(() => {
        modal.style.opacity = "1";
        content.style.transform = "translateY(0)";
    });

    // Close button
    document.getElementById("closeModal").addEventListener("click", () => {
        // Animate out
        modal.style.opacity = "0";
        content.style.transform = "translateY(-20px)";
        setTimeout(() => modal.remove(), 300); // remove after animation
    });
}

export function getFormData() {
  return {
    fullName: document.getElementById("fullName").value,
    studentId: document.getElementById("studentId").value,
    vPlate: document.getElementById("vPlate").value,
    vType: document.getElementById("vType").value,
    rDate: document.getElementById("rDate").value,
    startTime: document.getElementById("startTime").value,
    endTime: document.getElementById("endTime").value,
    spot: selectedSpot
  }
}