import javascriptLogo from './javascript.svg'
import viteLogo from '/vite.svg'
import { setupCounter } from './counter.js'
import './style.css';
import "./utils/auth.js";
import "./summary.js";
// THIS MAIN ARE ALL IN INDEX.HTML

// UNAUTHORIZED MODAL IN INDEX.HTML
document.addEventListener("DOMContentLoaded" , () => {
    document.getElementById("goReservationButton").addEventListener("click", async (e) => {
    e.preventDefault(); // block navigation for now
    
    const res = await fetch("http://localhost/ParkEase/BA-3104/backend/public/check-auth", {
      method: "GET",
      credentials: "include"
    });
    const modalToggle = document.getElementById("authBlockModal");
    console.log(modalToggle)
    if (!res.ok) {
      // User is NOT logged in → show modal
      modalToggle.nextElementSibling.classList.remove("hidden");
      setTimeout(() => {
        modalToggle.checked = true;
      }, 100)
      setTimeout(() => {
          window.location.href = "login.html";
      }, 1500)

      return;
    }

    // User is logged in → allow navigation
    window.location.href = "reservation.html";
  });
})


// main 
// document.querySelector('#app').innerHTML = `
//   <div class="text-5xl">
    
//   </div>
// `

// setupCounter(document.querySelector('#counter'))
