import React, { useEffect, useRef, useState } from 'react';
import next from '../assets/next-icon.png';
import back from '../assets/back-icon.png';
import user1 from '../assets/user-1.png';
import Title from './Title';
import { api, mediaUrl } from '../api';
function Testimonials() {
  const [items, setItems] = useState([]); const slider = useRef(null);
  useEffect(() => { api('/testimonials').then(r => setItems(r.data || [])).catch(() => setItems([])); }, []);
  const fallback = [{ student_name: 'Educity student', course_or_role: 'Educity', quote: 'A supportive and inspiring learning environment.', image_path: user1 }];
  const list = items.length ? items : fallback;
  const slide = direction => slider.current?.scrollBy({ left: direction * 420, behavior: 'smooth' });
  return <div id="testimonial" className="ml-30 mr-30 mt-20 mb-15"><Title heading="Testimonials" sub="What Students Say" /><div className="mt-18 flex items-center justify-center gap-5"><button aria-label="Previous testimonials" onClick={() => slide(-1)} className="rounded-full bg-blue-900 p-3"><img src={back} alt="" /></button><div ref={slider} className="flex max-w-4xl gap-7 overflow-hidden">{list.map(user => <article key={user.id || user.student_name} className="flex w-100 shrink-0 gap-4 shadow-xl"><img className="h-20 w-20 rounded-full border-2 border-blue-950 object-cover" src={mediaUrl(user.image_path) || user1} alt={user.student_name} /><div><p className="font-bold text-blue-900">{user.student_name}</p><h2 className="text-xl font-bold text-blue-950">{user.course_or_role}</h2><p>{user.quote}</p></div></article>)}</div><button aria-label="Next testimonials" onClick={() => slide(1)} className="rounded-full bg-blue-900 p-3"><img src={next} alt="" /></button></div></div>;
}
export default Testimonials;
