import javascriptLogo from './javascript.svg'
import viteLogo from '/vite.svg'
import { setupCounter } from './counter.js'
import './style.css'

// main 
document.querySelector('#app').innerHTML = `
  <div class="text-5xl">
    
  </div>
`

setupCounter(document.querySelector('#counter'))
