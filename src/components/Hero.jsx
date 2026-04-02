import React from 'react'
import hero from "../assets/hero.png";
import darkarrow from "../assets/dark-arrow.png"
function Hero() {
  return (
    <div id='hero'
      style={{
        backgroundImage: `linear-gradient(rgba(8,0,58,0.7), rgba(8,0,58,0.7)), url(${hero})`,
      }}
      className=" bg-cover bg-center h-screen text-white flex items-center justify-center "
    >
      <div className=" text-center">
        <h1 className="text-6xl font-semibold ">
          We ensure better education <br />
          for better world
        </h1>
        <p>
          Our cutting-edge cericulum is designed to empower students with the
          knowledge ,<br /> skills and the experience needed to excel int
          dynamic field of education
        </p>
        <div className=''>
          <button className="bg-white px-5 py-2 rounded-full text-black font-semibold mt-4 inline-flex items-center justify-center ">
            Explore More{" "}
            <img src={darkarrow} className="w-12 ml-3 " alt="" />
          </button>
        </div>
      </div>
    </div>
  );
}

export default Hero