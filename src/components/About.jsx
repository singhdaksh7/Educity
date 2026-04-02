import React from 'react'
import aboutImg from "../assets/about.png"
import play from "../assets/play-icon.png"
function About() {
  return (
    <div className="flex justify-center gap-8 ml-30 mr-30 mt-30 mb-20" id='about'>
      <div className="relative">
        <img className="rounded" src={aboutImg} alt="" />
        <img
          className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-15 h-15"
          src={play}
          alt=""
        />
      </div>
      <div className="mt-3">
        <p className="text-blue-900 font-bold">About University</p>
        <h1 className="text-xl text-blue-950 font-bold">
          Nurturing Tomorrow's <br />
          Leaders Today
        </h1>
        <br />
        <div className="text-gray-600">
          <p className="">
            Stanford University is a prestigious private research institution
            located in Stanford. Founded in 1885, it is known for academic
            excellence, innovation, and entrepreneurial spirit.{" "}
          </p>
          <br />
          <p>
            The university offers a wide range of undergraduate and graduate
            programs across seven schools. It has a strong emphasis on research,
            particularly in technology, engineering, and business. Stanford is
            closely connected to Silicon Valley, contributing significantly to
            global technological advancement.
          </p>
          <br />
          <p>
            Its vibrant campus life includes diverse student organizations,
            athletics, and cultural activities.
          </p>
        </div>
      </div>
    </div>
  );
}

export default About