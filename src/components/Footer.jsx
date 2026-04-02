import React from 'react'
import Title from './Title'

function Footer() {
  return (
    <div className="ml-30 mr-30 mt-30 mb-20 h-5 ">
      <div className=" border-t border-blue-950 flex justify-between items-center text-gray-700">
        <div className='flex gap-3 mt-4'>
          <p>© Educity, All Rights Reserved</p>
        </div>
        <div className='flex mt-4 gap-3'>
            <p>Terms of Services</p>
            <p>Terms of Privacy</p>
        </div>
      </div>
    </div>
  );
}

export default Footer