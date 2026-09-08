import React, { useEffect, useState } from 'react';
import Title from './Title';
import gallery1 from '../assets/gallery-1.png';
import gallery2 from '../assets/gallery-2.png';
import gallery3 from '../assets/gallery-3.png';
import gallery4 from '../assets/gallery-4.png';
import { api, mediaUrl } from '../api';
function Campus() { const [items, setItems] = useState([]); useEffect(() => { api('/gallery').then(r => setItems(r.data || [])).catch(() => setItems([])); }, []); const list = items.length ? items : [gallery1, gallery2, gallery3, gallery4].map((image_path, i) => ({ image_path, alt_text: `Campus photo ${i + 1}` })); return <div className="ml-30 mr-30 mt-20 mb-20" id="campus"><Title heading="Gallery" sub="Campus Photos" /><div className="mt-13 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">{list.slice(0, 8).map((item, i) => <img key={item.id || i} className="h-80 w-full rounded object-cover" src={mediaUrl(item.image_path)} alt={item.alt_text || item.title || 'Campus photo'} />)}</div></div>; }
export default Campus;
