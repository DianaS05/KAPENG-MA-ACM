let map,markers=[],infoWindow,userLocation=null,currentFilter='all',currentUser=null;

function initVibes(){
  map=new google.maps.Map(document.getElementById("map"),{zoom:12,center:{lat:14.5995,lng:120.9842},mapId:"VIBES_CHECK_CAFE_MAP"});
  infoWindow=new google.maps.InfoWindow();
  navigator.geolocation?.getCurrentPosition(p=>{userLocation={lat:p.coords.latitude,lng:p.coords.longitude};map.setCenter(userLocation);map.setZoom(14);showToast("📍 Location detected!");},()=>showToast("🔍 Enable location"));
  document.getElementById('searchInput')?.addEventListener('input',filterCafes);
  setTimeout(()=>{plotMarkers();setupEventListeners();loadUserFavorites();},500);
}

async function loadUserFavorites(){
  try{
    let res=await fetch('favorites_handler.php?action=get'),data=await res.json();
    if(data.status==='success'){
      let ids=data.favorites.map(f=>f.id);
      document.querySelectorAll('.favorite-btn').forEach(btn=>{if(ids.includes(parseInt(btn.dataset.cafeId))){btn.classList.add('active');btn.innerHTML='<i class="fas fa-heart"></i>';}});
      let sesh=await fetch('check_session.php'),user=await sesh.json();
      if(user.status==='success'&&user.user){currentUser=user.user;updateUI(user.user);}
    }
  }catch(e){}
}

function setupEventListeners(){
  document.getElementById('nearbyBtn')?.addEventListener('click',filterNearby);
  document.getElementById('favoritesLink')?.addEventListener('click',showFavs);
  document.querySelectorAll('.filter-chip').forEach(c=>c.addEventListener('click',()=>{filterByAmenity(c.dataset.filter);document.querySelectorAll('.filter-chip').forEach(c=>c.classList.remove('active'));c.classList.add('active');}));
  document.querySelectorAll('.locate-btn').forEach(b=>b.addEventListener('click',e=>{e.stopPropagation();focusMap(parseFloat(b.dataset.lat),parseFloat(b.dataset.lng));}));
  document.querySelectorAll('.favorite-btn').forEach(b=>b.addEventListener('click',async e=>{e.stopPropagation();await toggleFav(b.dataset.cafeId,b);}));
  document.querySelectorAll('.review-btn').forEach(b=>b.addEventListener('click',e=>{e.stopPropagation();showReviews(b.dataset.cafeId,b.dataset.cafeName);}));
  let modal=document.getElementById('reviewModal');
  document.querySelector('.close').onclick=()=>modal.style.display='none';
  window.onclick=e=>{if(e.target==modal)modal.style.display='none';};
  document.getElementById('reviewForm')?.addEventListener('submit',submitReview);
}

function focusMap(lat,lng){if(map){map.setCenter({lat:parseFloat(lat),lng:parseFloat(lng)});map.setZoom(17);document.getElementById('map').scrollIntoView({behavior:'smooth'});}}

function plotMarkers(){
  markers.forEach(m=>m.marker.setMap(null));markers=[];
  document.querySelectorAll('.vibe-card').forEach(c=>{
    let lat=parseFloat(c.dataset.lat),lng=parseFloat(c.dataset.lng);
    if(!isNaN(lat)&&!isNaN(lng)&&lat!==0&&lng!==0){
      let marker=new google.maps.Marker({position:{lat,lng},map,title:c.querySelector('.card-header h3')?.innerText||'Cafe',animation:google.maps.Animation.DROP});
      marker.addListener('click',()=>{infoWindow.setContent(`<div style="padding:10px;font-family:'Plus Jakarta Sans',sans-serif;"><strong style="color:#3E2723;">${c.querySelector('.card-header h3')?.innerText}</strong><br><span style="color:#c47c3e;">${c.querySelector('.location-tag')?.innerText||''}</span><br></div>`);infoWindow.open(map,marker);});
      markers.push({marker,cardElement:c,searchString:((c.dataset.name||'')+' '+(c.querySelector('.card-header h3')?.innerText||'')+' '+(c.querySelector('.location-tag')?.innerText||'')).toLowerCase(),lat,lng,cafeId:parseInt(c.dataset.id),hasWifi:c.dataset.wifi==='1',hasSockets:c.dataset.sockets==='1',hasParking:c.dataset.parking==='1',hasPet:c.dataset.pet==='1'});
    }
  });
  if(markers.length&&!userLocation){let b=new google.maps.LatLngBounds();markers.forEach(m=>b.extend(m.marker.getPosition()));map.fitBounds(b);}
}

function filterCafes(){
  let q=document.getElementById('searchInput')?.value.toLowerCase().trim()||'';
  markers.forEach(m=>{
    let text=!q||m.searchString.includes(q),amenity=true;
    if(currentFilter!=='all'){switch(currentFilter){case'wifi':amenity=m.hasWifi;break;case'sockets':amenity=m.hasSockets;break;case'parking':amenity=m.hasParking;break;case'pet':amenity=m.hasPet;break;}}
    let show=text&&amenity;
    m.cardElement.style.display=show?"block":"none";
    m.marker.setMap(show?map:null);
  });
}

function filterByAmenity(a){currentFilter=a;filterCafes();showToast(a==='all'?"Showing all cafes":`Showing ${a} cafes only`);}

function filterNearby(){
  if(!userLocation){showToast("Getting location...");navigator.geolocation.getCurrentPosition(p=>{userLocation={lat:p.coords.latitude,lng:p.coords.longitude};applyNearby();},()=>showToast("⚠️ Enable location"));return;}
  applyNearby();
}

function applyNearby(){
  let R=6371,count=0,near=[];
  markers.forEach(m=>{
    let dLat=(m.lat-userLocation.lat)*Math.PI/180,dLon=(m.lng-userLocation.lng)*Math.PI/180,a=Math.sin(dLat/2)**2+Math.cos(userLocation.lat*Math.PI/180)*Math.cos(m.lat*Math.PI/180)*Math.sin(dLon/2)**2,d=R*2*Math.atan2(Math.sqrt(a),Math.sqrt(1-a));
    if(d<=5){m.cardElement.style.display='block';m.marker.setMap(map);count++;near.push(m);}
    else{m.cardElement.style.display='none';m.marker.setMap(null);}
  });
  if(near.length){let b=new google.maps.LatLngBounds();near.forEach(m=>b.extend(m.marker.getPosition()));map.fitBounds(b);}
  showToast(`📍 Found ${count} cafes within 5km`);
  document.querySelectorAll('.filter-chip').forEach(c=>c.classList.remove('active'));
  document.querySelector('.filter-chip[data-filter="all"]')?.classList.add('active');
  currentFilter='all';
}

async function showFavs(){
  if(!currentUser){showToast("❤️ Please login");return;}
  try{
    let res=await fetch('favorites_handler.php'),data=await res.json();
    if(data.status==='success'){
      let ids=data.favorites.map(f=>f.id),count=0;
      markers.forEach(m=>{if(ids.includes(m.cafeId)){m.cardElement.style.display='block';m.marker.setMap(map);count++;}else{m.cardElement.style.display='none';m.marker.setMap(null);}});
      showToast(`❤️ Showing ${count} favorite${count!==1?'s':''}`);
    }
  }catch(e){}
}

async function toggleFav(id,btn){
  if(!currentUser){showToast("❤️ Please login");return;}
  let action=btn.classList.contains('active')?'remove':'add',fd=new FormData();
  fd.append('action',action);fd.append('cafe_id',id);
  try{
    let res=await fetch('favorites_handler.php',{method:'POST',body:fd}),data=await res.json();
    if(data.status==='success'){
      if(action==='add'){btn.classList.add('active');btn.innerHTML='<i class="fas fa-heart"></i>';showToast("❤️ Added!");}
      else{btn.classList.remove('active');btn.innerHTML='<i class="far fa-heart"></i>';showToast("💔 Removed");}
    }
  }catch(e){}
}

async function showReviews(id,name){
  document.getElementById('modalCafeName').innerHTML=`📝 Reviews for ${name}`;
  document.getElementById('reviewCafeId').value=id;
  document.getElementById('reviewModal').style.display='block';
  try{
    let res=await fetch(`reviews_handler.php?cafe_id=${id}`),data=await res.json(),div=document.getElementById('existingReviews');
    if(data.reviews?.length){
      div.innerHTML='<h4 style="margin-bottom:10px;">📖 Community Reviews:</h4>';
      data.reviews.forEach(r=>{div.innerHTML+=`<div class="review-item"><div class="review-header"><span class="review-user">${escape(r.user_name)}</span><span class="review-rating">${'★'.repeat(r.rating)}${'☆'.repeat(5-r.rating)}</span></div><div class="review-comment">${escape(r.comment)}</div><div class="review-date">📅 ${new Date(r.created_at).toLocaleDateString('en-PH')}</div></div>`;});
    }else div.innerHTML='<p style="color:#8f745a;">✨ No reviews yet. Be the first!</p>';
  }catch(e){}
}

async function submitReview(e){
  e.preventDefault();
  if(!currentUser){showToast("Please login");return;}
  let id=document.getElementById('reviewCafeId').value,rating=document.querySelector('input[name="rating"]:checked')?.value,comment=document.querySelector('#reviewForm textarea').value;
  if(!rating){showToast("⭐ Select rating");return;}
  if(!comment.trim()){showToast("📝 Write a review");return;}
  let fd=new FormData();fd.append('cafe_id',id);fd.append('rating',rating);fd.append('comment',comment);
  try{
    let res=await fetch('reviews_handler.php',{method:'POST',body:fd}),data=await res.json();
    if(data.status==='success'){showToast("✨ Review submitted!");document.getElementById('reviewModal').style.display='none';document.querySelector('#reviewForm textarea').value='';if(document.querySelector('input[name="rating"]:checked'))document.querySelector('input[name="rating"]:checked').checked=false;setTimeout(()=>location.reload(),1500);}
    else showToast(data.message||"Error");
  }catch(e){}
}

function escape(t){let d=document.createElement('div');d.textContent=t;return d.innerHTML;}
function showToast(m){let t=document.getElementById('toastMsg');if(!t){t=document.createElement('div');t.id='toastMsg';t.className='toast-msg';document.body.appendChild(t);}t.textContent=m;t.style.opacity='1';setTimeout(()=>t.style.opacity='0',3000);}
window.handleCredentialResponse=async function(r){if(!r?.credential){showToast("Sign-in failed");return;}try{let res=await fetch('google_login.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({token:r.credential})}),data=await res.json();if(data.status==='success'){currentUser=data.user;updateUI(data.user);showToast(`☕ Welcome ${data.user.name.split(' ')[0]}!`);setTimeout(()=>location.reload(),1000);}else showToast("Sign-in failed");}catch(e){}};
function updateUI(u){let c=document.getElementById('googleSignInContainer');if(!c)return;c.innerHTML=`<div class="user-info"><img class="user-avatar" src="${u.avatar}" alt="${u.name}"><span class="user-name">${u.name.split(' ')[0]}</span><button class="sign-out-btn" id="signOutBtn" title="Sign Out"><i class="fas fa-sign-out-alt"></i></button></div>`;document.getElementById('signOutBtn')?.addEventListener('click',()=>{currentUser=null;fetch('logout.php').then(()=>location.reload());});}
function renderGoogleButton(){let c=document.getElementById('googleSignInContainer');if(!c)return;c.innerHTML='<div id="g_id_signin"></div>';if(window.google?.accounts){google.accounts.id.initialize({client_id:"482586349646-5cd2fd43nbio60b6bamv5fce7rgl6djs.apps.googleusercontent.com",callback:handleCredentialResponse,auto_select:false,cancel_on_tap_outside:true});google.accounts.id.renderButton(document.getElementById("g_id_signin"),{type:"standard",theme:"outline",size:"medium",text:"signin_with",shape:"rectangular",logo_alignment:"left",width:180});}else setTimeout(renderGoogleButton,500);}
window.initVibes=initVibes;window.focusMap=focusMap;
document.addEventListener('DOMContentLoaded',()=>{renderGoogleButton();if(typeof google!=='undefined'&&google.maps)initVibes();});