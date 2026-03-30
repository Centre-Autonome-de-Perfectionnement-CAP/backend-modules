const x=(e,a,r,g)=>{const t=e.length;let n=e.indexOf(a);return n===-1?!r&&g?e[t-1]:e[0]:(n+=r?1:-1,n=(n+t)%t,e[Math.max(0,Math.min(n,t-1))])};export{x as g};
