import React from 'react'
import NavBar from './components/NavBar'
import Hero from './components/Hero'
import Program from './components/Program'
import About from './components/About'
import Campus from './components/Campus'
import Testimonials from './components/Testimonials'
import Contacts from './components/Contacts'
import Footer from './components/Footer'


function App() {
  return (
    <div>
      <NavBar/>
      <Hero/>
      <Program/>
      <About/>
      <Campus/>
      <Testimonials/>
      <Contacts/>
      <Footer/>
    </div>
  )
}

export default App