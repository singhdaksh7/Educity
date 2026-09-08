import React from 'react'
import { Navigate, Route, Routes } from 'react-router-dom'
import Admin from './Admin'
import Student from './Student'
import ApplyForm from './components/ApplyForm'
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

function App() {
  return (
    <Routes>
      <Route path="/admin/*" element={<Admin/>}/>
      <Route path="/student/*" element={<Student/>}/>
      <Route path="/apply" element={<ApplyForm/>}/>
      {/* User-friendly aliases, kept distinct from /admin/login. */}
      <Route path="/login" element={<Navigate to="/student/login" replace/>}/>
      <Route path="/register" element={<Navigate to="/student/register" replace/>}/>
      <Route path="/dashboard" element={<Navigate to="/student" replace/>}/>
      <Route path="*" element={<PublicApp/>}/>
    </Routes>
  )
}

export default App
