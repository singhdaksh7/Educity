import React from 'react'
import { Route, Routes } from 'react-router-dom'
import Admin from './Admin'
import NavBar from './components/NavBar'
import Hero from './components/Hero'
import Program from './components/Program'
import About from './components/About'
import Campus from './components/Campus'
import Testimonials from './components/Testimonials'
import Contacts from './components/Contacts'
import Footer from './components/Footer'


function PublicApp() {
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

function App() { return <Routes><Route path="/admin/*" element={<Admin/>}/><Route path="*" element={<PublicApp/>}/></Routes> }

export default App
