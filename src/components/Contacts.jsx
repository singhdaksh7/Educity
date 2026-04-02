import React from 'react'
import Title from './Title'
import msg from "../assets/msg-icon.png"
import mail from "../assets/mail-icon.png"
import phone from "../assets/phone-icon.png"
import location from "../assets/location-icon.png"
import arrow from "../assets/white-arrow.png"
import { useState } from 'react'
function Contacts() {
     const [result, setResult] = useState("");

  const onSubmit = async (event) => {
    event.preventDefault();
    setResult("Sending....");
    const formData = new FormData(event.target);
    formData.append("access_key", "c0d543a7-af1b-4e2a-9303-2f9f96c0a496");

    const response = await fetch("https://api.web3forms.com/submit", {
      method: "POST",
      body: formData
    });

    const data = await response.json();
    if (data.success) {
      setResult("Form Submitted Successfully");
      event.target.reset();
    } else {
      setResult("Error");
    }
  }
  return (
    <div id='contact'>
      <Title heading="contact us" sub="Get In Touch" />
      {/* main */}
      <div className="flex gap-15 ml-30 mr-30 mt-10 mb-20">
        <div>
          <h1 className="text-2xl text-blue-900 font-bold flex items-center gap-5 ">
            Send Us a Message <img src={msg} alt="" />
          </h1>

          <p className="text-left mt-4 text-gray-600">
            "Need help? We're here for you. Contact us anytime for support or
            guidance. <br /> Our team is ready to assist with any questions.
            Your concerns are important, <br />
            and we’re just a message away."
          </p>
          <ul className="flex flex-col gap-3 mt-5">
            <li className="flex gap-2 items-center">
              {" "}
              <img className="w-10 h-7 " src={mail} alt="" />{" "}
              Contact@GreatStack.com
            </li>
            <li className="flex gap-2 items-center">
              <img className="w-10 h-7 " src={phone} alt="" /> +91 123-456-7890
            </li>
            <li className="flex gap-2 items-center">
              <img className="w-10 h-7 " src={location} alt="" /> Aanand Vihar
              12345 near Delhi
            </li>
          </ul>
        </div>
        <form action="" onSubmit={onSubmit}>
          <div className="flex flex-col">
            <label htmlFor="">Your Name:</label>
            <input
              type="text"
              placeholder="Enter Your Name "
              className="bg-blue-200 mt-2 mb-3 h-10 w-100 rounded placeholder:px-4"
            />

            <label htmlFor="">Your PhoneNo.</label>
            <input
              type="text"
              placeholder="Enter Your Phone-Number"
              className="bg-blue-200 mt-2 mb-3 h-10 w-100 rounded placeholder:px-4"
            />

            <label htmlFor="">Your Message:</label>
            <textarea
              name=""
              id=""
              placeholder="Enter Your Message"
              rows={6}
              className="bg-blue-200 mt-2 mb-3 w-100 rounded placeholder:py-2 px-4"
            ></textarea>

            <div className="flex justify-center items-center">
              <button
                className="bg-blue-950 text-white flex  rounded-full py-2 px-3 w-25 text-center  gap-2"
                type="submit"
              >
                Submit <img className="w-5 h-5" src={arrow} alt="" />
              </button>
            </div>
          </div>
          <span className='mt-4'>{result}</span>
        </form>
      </div>
    </div>
  );
}

export default Contacts