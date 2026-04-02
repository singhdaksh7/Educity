import React, { useRef } from 'react'
import next from "../assets/next-icon.png"
import back from "../assets/back-icon.png"
import user1 from "../assets/user-1.png"
import user2 from "../assets/user-2.png"
import user3 from "../assets/user-3.png"
import user4 from "../assets/user-4.png"
import arrow from "../assets/white-arrow.png"
import Title from './Title'
function Testimonials() {
    const users = [
      {
        img: user1,
        name: "William Jackson",
        uni: "Educity",
        text: "I am proud to study at Edusity University because it provides a supportive and inspiring learning environment. The professors are helpful, and the campus offers great opportunities for both academic and personal growth.",
      },
      {
        img: user2,
        name: "Sarika Panwar",
        uni: "Educity",
        text: "I am proud to study at Edusity University because it provides a supportive and inspiring learning environment. The professors are helpful, and the campus offers great opportunities for both academic and personal growth.",
      },
       {
            img: user3,
            name: "Vanshika Goyal",
            uni: "Educity",
            text: "I am proud to study at Edusity University because it provides a supportive and inspiring learning environment. The professors are helpful, and the campus offers great opportunities for both academic and personal growth."
      },
      {
            img: user4,
            name: "Shruti Sharma",
            uni: "Educity",
            text: "I am proud to study at Edusity University because it provides a supportive and inspiring learning environment. The professors are helpful, and the campus offers great opportunities for both academic and personal growth."
      }
    ];

    const SliderRef = useRef(null);
    const slideLeft = () =>{
        SliderRef.current.scrollBy({left:-420 , behaviour: 'smooth'})
    }
    const slideRight = () => {
      SliderRef.current.scrollBy({ left: 420, behaviour: 'smooth' });
    };
  return (
    <div className="" id='testimonial'>
      <div className=" ml-30 mr-30 mt-20 mb-15">
        <Title heading="Gallery" sub="College Photos" />
        <div className="flex justify-center items-center object-cover gap-20 mt-18">
          <div className="w-20 h-10  bg-blue-900 rounded-full flex items-center justify-center">
            <img
              className=" w-5 h-5 rounded-full object-cover"
              src={back}
              onClick={slideLeft}
              alt=""
            />
          </div>
          <div ref={SliderRef} className=" scrollbar-hide flex gap-7 overflow-hidden">
            {users.map((user) => {
              return (
                <div className="flex shrink-0">
                  <div className="flex gap-4 w-100 h-60 bg-transparent shadow-xl">
                    <img
                      className="rounded-full w-20 h-20 object-cover border-2 border-blue-950"
                      src={user.img}
                      alt=""
                    />
                    <div className="flex flex-col items-center justify-center">
                      <p className="font-bold text-blue-900">{user.name}</p>
                      <h2 className="text-xl font-bold text-blue-950">
                        {user.uni}
                      </h2>
                      <div>
                        <p className="text-left w-70">{user.text}</p>
                      </div>
                    </div>
                  </div>
                </div>
              );
            })}
          </div>
          <div className="w-20 h-10 bg-blue-900 rounded-full flex items-center justify-center">
            <img
              className="rounded-full w-5 h-5 object-cover"
              src={next}
              onClick={slideRight}
              alt=""
            />
          </div>
        </div>
      </div>
      <div className="flex flex-row justify-center items-center mb-3 ">
        <button className="bg-blue-950 text-white rounded-full px-4 py-3 flex justify-center items-center gap-2 ">
          Explore More{" "}
          <img
            src={arrow}
            className=" w-8 h-4 rounded-full object-cover "
            alt=""
          />
        </button>
      </div>
    </div>
  );
}
export default Testimonials;