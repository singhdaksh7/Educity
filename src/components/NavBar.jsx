import React, { useEffect, useState } from 'react'
import{ Link } from "react-scroll"
import logo from '../assets/logo.png'
function NavBar() {
  const [sticky , setSticky] = useState(false);

  useEffect(()=>{
         window.addEventListener('scroll',()=>{
           window.scrollY > 50 ? setSticky(true) : setSticky(false)
         })
  },[])
  return (
    <div>
      <nav className={`flex items-center justify-between h-16 fixed w-full z-50 ${sticky ? "bg-blue-950" : ""}   `}>
       
          <img src={logo} className="object-cover h-10 ml-8 " alt="logo" />
          <div className='flex gap-10 md:gap-6 sm:gap-3 cursor-pointer text-white'>
            <Link to='hero' duration={500} smooth={true} offset={-150}>Home</Link>
            <Link to='about' duration={500} smooth={true} offset={-150}>About Us</Link>
            <Link to='program' duration={500} smooth={true} offset={-90}>Program</Link>
            <Link to='contact' duration={500} smooth={true} offset={-90}>Contact us</Link>
            <Link to='testimonial' duration={500} smooth={true} offset={-90}>Testimonials</Link>
          </div>
        <div>
            <button className='bg-white hover:bg-gray-400 px-5  py-2  rounded font-bold  mr-8'>Contact Us</button>
        </div> 
      </nav>
    </div>
  );
}

export default NavBar