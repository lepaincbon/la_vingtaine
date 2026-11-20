import * as THREE from 'three';

(() => {
  const canvas = document.getElementById('bottle-scene');
  if (!canvas) return;

  const loader = document.getElementById('loader');
  let loaderHidden = false;

  const hideLoader = () => {
    if (!loader || loaderHidden) return;
    loaderHidden = true;
    loader.classList.add('is-hidden');
    setTimeout(() => {
      if (loader.parentNode) loader.remove();
    }, 700);
  };

  window.addEventListener('load', hideLoader);
  window.addEventListener('pageshow', hideLoader);
  document.addEventListener('DOMContentLoaded', () => {
    // Hide faster on DOM ready, but give it a tiny bit of "fancy" breathing room
    setTimeout(hideLoader, 500);
  });
  // Safety fallback
  setTimeout(hideLoader, 2000);

  const titleEl = document.getElementById('hero-bottle-title');
  const metaEl = document.getElementById('hero-bottle-meta');
  const textEl = document.getElementById('hero-bottle-text');
  const descriptionWrap = document.getElementById('hero-bottle-description');
  const jsonDataEl = document.getElementById('hero-bottles-data');
  const scrollSection = document.getElementById('hero-scroll');

  let bottleDescriptions = [];
  try {
    bottleDescriptions = jsonDataEl ? JSON.parse(jsonDataEl.textContent || '[]') : [];
  } catch (e) {
    bottleDescriptions = [];
  }
  if (!bottleDescriptions.length) {
    hideLoader();
    return;
  }

  const scene = new THREE.Scene();
  scene.background = new THREE.Color(0xf7f1e8);

  const renderer = new THREE.WebGLRenderer({
    canvas,
    antialias: true,
    alpha: true
  });
  renderer.outputColorSpace = THREE.SRGBColorSpace;
  renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));

  const camera = new THREE.PerspectiveCamera(38, 1, 0.1, 100);
  camera.position.set(0, 0.8, 6.2);
  scene.add(camera);

  scene.add(new THREE.AmbientLight(0xffffff, 1.05));

  const keyLight = new THREE.DirectionalLight(0xffffff, 1.2);
  keyLight.position.set(3.5, 6, 4.5);
  scene.add(keyLight);

  const fillLight = new THREE.DirectionalLight(0xd9b98f, 0.75);
  fillLight.position.set(-3.5, 3, -2);
  scene.add(fillLight);

  const rimLight = new THREE.DirectionalLight(0xc9a34f, 0.5);
  rimLight.position.set(0, 4, -6);
  scene.add(rimLight);

  const bodyGeometry = new THREE.CylinderGeometry(0.44, 0.5, 2.35, 40, 1, false);
  const shoulderGeometry = new THREE.CylinderGeometry(0.34, 0.44, 0.36, 40);
  const neckGeometry = new THREE.CylinderGeometry(0.17, 0.2, 0.58, 28);
  const labelGeometry = new THREE.CylinderGeometry(0.445, 0.445, 1.1, 32, 1, true, -0.7, 1.4);

  const getColorByType = (type) => {
    const t = (type || '').toLowerCase();
    if (t.includes('champagne')) return 0xc9a34f;
    if (t.includes('blanc')) return 0xf0e7b2;
    if (t.includes('rouge')) return 0x5a1022;
    if (t.includes('spiritueux')) return 0xb8742c;
    return 0x6b3f28;
  };

  const bottles = bottleDescriptions.map((item) => {
    const color = getColorByType(item.type);
    const material = new THREE.MeshStandardMaterial({
      color,
      roughness: 0.3,
      metalness: 0.2
    });

    const labelMaterial = new THREE.MeshStandardMaterial({
      color: 0xf7f1e8,
      roughness: 0.85,
      metalness: 0.02,
      side: THREE.DoubleSide
    });

    const group = new THREE.Group();

    const body = new THREE.Mesh(bodyGeometry, material);
    const shoulder = new THREE.Mesh(shoulderGeometry, material);
    shoulder.position.y = 1.34;
    const neck = new THREE.Mesh(neckGeometry, material);
    neck.position.y = 1.8;

    const label = new THREE.Mesh(labelGeometry, labelMaterial);
    label.position.y = -0.1;

    group.add(body);
    group.add(shoulder);
    group.add(neck);
    group.add(label);
    scene.add(group);

    return group;
  });

  const setSize = () => {
    const parent = canvas.parentElement;
    const width = Math.max(parent ? parent.clientWidth : window.innerWidth, 320);
    const height = Math.max(parent ? parent.clientHeight : 420, 320);
    camera.aspect = width / height;
    camera.updateProjectionMatrix();
    renderer.setSize(width, height, false);
  };

  setSize();
  window.addEventListener('resize', setSize);

  const setDescription = (index) => {
    const data = bottleDescriptions[index];
    if (!data || !titleEl || !metaEl || !textEl) return;
    titleEl.textContent = data.name || '—';
    metaEl.textContent = `${data.type || ''}${data.region ? ' • ' + data.region : ''}`;
    textEl.textContent = data.description || '—';
  };

  let currentIndex = 0;
  setDescription(0);

  const getSectionProgress = () => {
    if (!scrollSection) return 0;
    const rect = scrollSection.getBoundingClientRect();
    const totalScrollable = Math.max(1, rect.height - window.innerHeight);
    return Math.min(Math.max(-rect.top / totalScrollable, 0), 1);
  };

  const animate = () => {
    const total = bottleDescriptions.length;
    const globalProgress = getSectionProgress();

    const maxIndex = Math.max(0, total - 1);
    const exactScrollIndex = globalProgress * maxIndex;
    const nextIndex = Math.round(exactScrollIndex);

    if (nextIndex !== currentIndex) {
      currentIndex = nextIndex;
      setDescription(currentIndex);
    }

    let nearestDistance = Number.POSITIVE_INFINITY;

    bottles.forEach((bottle, i) => {
      const offset = i - exactScrollIndex;
      const absOffset = Math.abs(offset);

      bottle.position.x = 0;
      bottle.position.y = -offset * 4.5;
      bottle.position.z = 0;

      bottle.rotation.y = -offset * Math.PI * 1.2;
      bottle.rotation.z = Math.sin(offset * Math.PI) * 0.12;
      bottle.visible = absOffset < 1.5;

      if (absOffset < nearestDistance) nearestDistance = absOffset;
    });

    let blurStrength = 0;
    if (nearestDistance > 0.35 && nearestDistance < 0.5) {
      blurStrength = (nearestDistance - 0.35) / (0.5 - 0.35);
    } else if (nearestDistance >= 0.5) {
      blurStrength = 1;
    }

    if (descriptionWrap) {
      descriptionWrap.style.filter = `blur(${(blurStrength * 12).toFixed(2)}px)`;
      descriptionWrap.style.opacity = `${(1 - blurStrength * 0.55).toFixed(3)}`;
    }

    renderer.render(scene, camera);
    hideLoader();
    requestAnimationFrame(animate);
  };

  animate();
})();
