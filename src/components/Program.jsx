import React from 'react'
import program1 from "../assets/program-1.png"
import program2 from "../assets/program-2.png"
import program3 from "../assets/program-3.png"
import programIcon1 from "../assets/program-icon-1.png"
import programIcon2 from "../assets/program-icon-2.png"
import programIcon3 from "../assets/program-icon-3.png"
import Title from './Title'
function Program() {
  return (
    <div  id='program'>
      <Title heading='Our Program' sub='What We Offer'/>
      <div className="flex gap-10 ml-30 mr-30 mt-20 mb-20 h-79 ">
        <div className="relative ">
          <img className="rounded" src={program1} alt="" />
          <div className="absolute top-0 right-0 bottom-0 left-0 bg-[linear-gradient(rgba(0,15,153,0.3))] h-full flex justify-center items-center flex-col text-white opacity-0 hover:opacity-100  ">
            <img
              className="w-20 h-20 ml-2 mt-1 p-[60%]  duration-100 hover:p-0"
              src={programIcon1}
              alt=""
            />
            <p>Graduation Degree</p>
          </div>
        </div>
        <div className="relative">
          <img className="rounded" src={program2} alt="" />
          <div className="absolute top-0 right-0 bottom-0 left-0 bg-[linear-gradient(rgba(0,15,153,0.2))] h-full flex justify-center items-center flex-col text-white opacity-0 hover:opacity-100  ">
            <img
              className="w-20 h-20 ml-2 mt-1 p-[60%] duration-100 hover:p-0"
              src={programIcon2}
              alt=""
            />
            <p> Post Graduation </p>
          </div>
        </div>
        <div className="relative">
          <img className="rounded" src={program3} alt="" />
          <div className="absolute top-0 right-0 bottom-0 left-0 bg-[linear-gradient(rgba(0,15,153,0.3))] h-full flex justify-center items-center flex-col text-white opacity-0 hover:opacity-100 ">
            <img
              className="w-20 h-20 ml-2 mt-1 p-[60%]  duration-100 hover:p-0"
              src={programIcon3}
              alt=""
            />
            <p>Graduation Degree</p>
          </div>
        </div>
      </div>
    </div>
  );
}

export default Program