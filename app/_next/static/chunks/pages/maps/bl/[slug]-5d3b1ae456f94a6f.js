(self.webpackChunk_N_E=self.webpackChunk_N_E||[]).push([[468],{7655:function(e,t,n){(window.__NEXT_P=window.__NEXT_P||[]).push(["/maps/bl/[slug]",function(){return n(9488)}])},6794:function(e,t,n){"use strict";var r=n(5893),i=n(7294),l=n(5700),s=n(5973),a=n(2705),c=n(1162),o=n(3376),d=n(4626),u=n(6835),x=n(7539),g=n(1345),f=n(8958);n(8891),t.Z=function(e){let{data:t}=e,[n,h]=(0,i.useState)(),[m,p]=(0,i.useState)({}),b=(0,i.useRef)(null),w=(0,i.useRef)();w.current=n,(0,i.useEffect)(()=>{if(b.current){let e=new a.Z({source:new o.Z({features:new s.Z({featureProjection:"EPSG:3857"}).readFeatures(t)}),style:new x.ZP({fill:new g.Z({color:"rgba(0, 0, 255, 0.2)"}),stroke:new f.Z({color:"blue",width:3})})}),n=new l.Z({target:b.current,layers:[new c.Z({source:new d.Z}),e]}),r=n.getView(),{bbox:i}=t.properties,m=(0,u.$A)(i,"EPSG:4326","EPSG:3857");return r.fit(m,{size:n.getSize(),padding:[50,50,50,50]}),n.on("click",j),h(n),()=>{n.setTarget("")}}},[t]);let j=e=>{if(w.current){let t=w.current.forEachFeatureAtPixel(e.pixel,e=>e);if(t){let e=t.getProperties(),{name:n,url:r,activities:i,"logo-url":l,contact:s,description:a,"site-url":c}=e,o={name:n,url:r,description:a,logo:(null==l?void 0:l.thumb)||l||"",activities:i,email:s,website:c};p(o)}else p({})}};return(0,r.jsxs)("div",{children:[(0,r.jsx)("div",{id:"map",ref:b,style:{height:"100vh"}}),(0,r.jsx)("div",{id:"popup",className:"ol-popup absolute m-6 right-0 bottom-0",children:m.name&&(0,r.jsxs)("div",{className:"border border-gray-200 p-6 rounded-lg bg-white",children:[m.logo&&(0,r.jsx)("div",{className:"w-10 h-10 inline-flex items-center justify-center rounded-full bg-indigo-100 text-indigo-500 mb-4",children:(0,r.jsx)("img",{src:m.logo,width:40,height:40,alt:m.name})}),(0,r.jsx)("h2",{className:"text-lg text-gray-900 font-medium title-font mb-2",children:m.name}),m.description&&(0,r.jsx)("p",{className:"leading-relaxed text-base",children:(0,r.jsx)("div",{dangerouslySetInnerHTML:{__html:m.description}})}),m.activities&&(0,r.jsx)("p",{className:"leading-relaxed text-base",children:m.activities.join(", ")}),(m.email||m.website)&&(0,r.jsxs)("div",{className:"text-center leading-none flex w-full",children:[(0,r.jsx)("span",{className:"text-gray-400 mr-3 inline-flex items-center leading-none text-base pr-3 py-1 border-r-2 border-gray-200",children:m.email}),(0,r.jsx)("span",{className:"text-gray-400 inline-flex items-center leading-none text-base",children:(0,r.jsx)("a",{href:m.website,target:"_blank",children:m.website})})]}),m.url&&(0,r.jsx)("div",{className:"text-center leading-none flex w-full",children:(0,r.jsx)("span",{className:"text-gray-400 mr-3 inline-flex items-center leading-none text-base pr-3 py-1 border-gray-200",children:(0,r.jsx)("a",{href:m.url,target:"_top",children:"DIMB Website"})})})]})})]})}},9488:function(e,t,n){"use strict";n.r(t),n.d(t,{__N_SSG:function(){return l}});var r=n(5893);n(7294);var i=n(6794),l=!0;t.default=e=>{let{data:t}=e;return t?(0,r.jsx)(i.Z,{data:t}):null}}},function(e){e.O(0,[239,774,888,179],function(){return e(e.s=7655)}),_N_E=e.O()}]);