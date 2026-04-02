import React from 'react'

function Title({heading , sub}) {
  return (
    <div>
        <div className='text-center mt-20 '>
            <p className="uppercase text-blue-900 font-bold">{heading}</p>
            <h1 className='text-blue-950 font-bold text-2xl'>{sub}</h1>
        </div>
    </div>
  )
}

export default Title