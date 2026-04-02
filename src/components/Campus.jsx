import React from 'react'
import Title from './Title'
import gallery1 from '../assets/gallery-1.png'
import gallery2 from '../assets/gallery-2.png'
import gallery3 from '../assets/gallery-3.png'
import gallery4 from '../assets/gallery-4.png'
import arrow from '../assets/white-arrow.png'
function Campus() {
  return (
    <div className=" ml-30 mr-30 mt-20 mb-20" id='campus'>
      <Title heading="Gallery" sub="Campus Photos" />
      <div className="flex flex-col justify-center items-center gap-10 mt-13">
        <div className="grid grid-cols-4 gap-6 ">
          <img
            className="rounded w-100 h-80 object-cover"
            src={gallery1}
            alt=""
          />
          <img
            className="rounded w-100 h-80 object-cover"
            src={gallery2}
            alt=""
          />
          <img
            className="rounded w-100 h-80 object-cover"
            src={gallery3}
            alt=""
          />
          <img
            className="rounded w-100 h-80 object-cover"
            src={gallery4}
            alt=""
          />
        </div>
        <div className=''>
          <button className="bg-blue-950 text-white px-6 py-3 rounded-full flex gap-3">
            Explore More <img className='w-8 'src={arrow} alt="" />
          </button>
        </div>
      </div>
    </div>
  );
}

export default Campus