import * as THREE from 'three';

(() => {
  const loader = document.getElementById('loader');

  window.addEventListener('load', () => {
    if (loader) {
      loader.classList.add('is-hidden');
      setTimeout(() => {
        if (loader.parentNode) loader.remove();
      }, 700);
    }
  });

  const canvas = document.getElementById('bottle-scene');
  if (!canvas) return;

  const titleEl = document.getElementById('hero-bottle-title');
  const metaEl = document.getElementById('hero-bottle-meta');
  const textEl = document.getElementById('hero-bottle-text');
  const descriptionWrap = document.getElementById('hero-bottle-description');
  const jsonDataEl = document.getElementById('hero-bottles-data');

  let bottleDescriptions = [];
  try {
    bottleDescriptions = jsonDataEl ? JSON.parse(jsonDataEl.textContent || '[]') : [];
  } catch (e) {
    bottleDescriptions = [];
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

  const camera = new THREE.PerspectiveCamera(40, 1, 0.1, 100);
  camera.position.set(0, 1.8, 9);
  scene.add(camera);

  scene.add(new THREE.AmbientLight(0xffffff, 1.1));

  const keyLight = new THREE.DirectionalLight(0xffffff, 1.2);
  keyLight.position.set(4, 8, 6);
  scene.add(keyLight);

  const fillLight = new THREE.DirectionalLight(0xd9b98f, 0.8);
  fillLight.position.set(-4, 3, -2);
  scene.add(fillLight);

  const rimLight = new THREE.DirectionalLight(0xc9a34f, 0.45);
  rimLight.position.set(0, 4, -8);
  scene.add(rimLight);

  const bottlesGroup = new THREE.Group();
  scene.add(bottlesGroup);

  const bottleCount = Math.max(1, bottleDescriptions.length || 10);
  const radius = 4.2;
  const bodyGeometry = new THREE.CylinderGeometry(0.35, 0.45, 2.2, 32, 1, false);
  const neckGeometry = new THREE.CylinderGeometry(0.18, 0.22, 0.5, 24);

  const palette = [0x6b3f28, 0xc8ad8d, 0xc9a34f, 0x7a4f35, 0xb58e6b];

  for (let i = 0; i < bottleCount; i++) {
    const angle = (i / bottleCount) * Math.PI * 2;
    const color = palette[i % palette.length];

    const material = new THREE.MeshStandardMaterial({
      color,
      roughness: 0.32,
      metalness: 0.18
    });

    const bottle = new THREE.Mesh(bodyGeometry, material);
    const neck = new THREE.Mesh(neckGeometry, material);
    neck.position.y = 1.2;
    bottle.add(neck);

    bottle.position.set(Math.cos(angle) * radius, 0, Math.sin(angle) * radius);
    bottle.lookAt(0, 0.6, 0);
    bottle.userData = { angleOffset: angle, bobOffset: i * 0.5, index: i };
    bottlesGroup.add(bottle);
  }

  const floor = new THREE.Mesh(
    new THREE.CircleGeometry(8, 64),
    new THREE.MeshStandardMaterial({ color: 0xffffff, roughness: 0.95, metalness: 0.02 })
  );
  floor.rotation.x = -Math.PI / 2;
  floor.position.y = -1.3;
  scene.add(floor);

  const setSize = () => {
    const parent = canvas.parentElement;
    const width = Math.max(parent ? parent.clientWidth : window.innerWidth, 320);
    const height = Math.max(parent ? parent.clientHeight : 500, 320);
    camera.aspect = width / height;
    camera.updateProjectionMatrix();
    renderer.setSize(width, height, false);
  };

  setSize();
  window.addEventListener('resize', setSize);

  let scrollProgress = 0;
  let targetScrollProgress = 0;
  let currentIndex = -1;
  let transitionLock = false;

  const updateDescription = (index) => {
    if (!bottleDescriptions.length || !titleEl || !metaEl || !textEl || !descriptionWrap) return;
    const data = bottleDescriptions[index % bottleDescriptions.length];
    if (!data) return;

    if (transitionLock) return;
    transitionLock = true;

    descriptionWrap.style.filter = 'blur(6px)';
    descriptionWrap.style.opacity = '0.45';

    setTimeout(() => {
      titleEl.textContent = data.name || '—';
      metaEl.textContent = `${data.type || ''}${data.region ? ' • ' + data.region : ''}`;
      textEl.textContent = data.description || '—';
      descriptionWrap.style.filter = 'blur(0px)';
      descriptionWrap.style.opacity = '1';
      transitionLock = false;
    }, 180);
  };

  if (bottleDescriptions.length > 0) updateDescription(0);

  window.addEventListener(
    'wheel',
    (event) => {
      targetScrollProgress += event.deltaY * 0.00035;
      const max = bottleCount * 10;
      targetScrollProgress = Math.max(0, Math.min(targetScrollProgress, max));
    },
    { passive: true }
  );

  const clock = new THREE.Clock();

  const animate = () => {
    const elapsed = clock.getElapsedTime();
    scrollProgress += (targetScrollProgress - scrollProgress) * 0.08;

    bottlesGroup.children.forEach((bottle) => {
      const base = bottle.userData.angleOffset || 0;
      const bobOffset = bottle.userData.bobOffset || 0;
      const angle = base + scrollProgress * 0.55;

      bottle.position.x = Math.cos(angle) * radius;
      bottle.position.z = Math.sin(angle) * radius;
      bottle.position.y = Math.sin(elapsed * 1.5 + bobOffset) * 0.08;

      bottle.rotation.y += 0.004;
      bottle.lookAt(0, 0.55, 0);
    });

    const normalized = ((scrollProgress * 0.55) % (Math.PI * 2) + Math.PI * 2) % (Math.PI * 2);
    const index = Math.floor((normalized / (Math.PI * 2)) * bottleCount) % bottleCount;
    if (index !== currentIndex) {
      currentIndex = index;
      updateDescription(currentIndex);
    }

    bottlesGroup.rotation.y = Math.sin(elapsed * 0.22) * 0.08;
    renderer.render(scene, camera);
    requestAnimationFrame(animate);
  };

  animate();
})();
