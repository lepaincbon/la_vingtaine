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
  const scrollSection = document.getElementById('hero-scroll');

  let bottleDescriptions = [];
  try {
    bottleDescriptions = jsonDataEl ? JSON.parse(jsonDataEl.textContent || '[]') : [];
  } catch (e) {
    bottleDescriptions = [];
  }
  if (!bottleDescriptions.length) return;

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
  camera.position.set(0, 1.2, 6.2);
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

  const bottleGroup = new THREE.Group();
  scene.add(bottleGroup);

  const bodyGeometry = new THREE.CylinderGeometry(0.44, 0.5, 2.35, 40, 1, false);
  const shoulderGeometry = new THREE.CylinderGeometry(0.34, 0.44, 0.36, 40);
  const neckGeometry = new THREE.CylinderGeometry(0.17, 0.2, 0.58, 28);

  const palette = [0x6b3f28, 0xc8ad8d, 0xc9a34f, 0x7a4f35, 0xb58e6b];

  const bottleMaterial = new THREE.MeshStandardMaterial({
    color: palette[0],
    roughness: 0.3,
    metalness: 0.2
  });

  const body = new THREE.Mesh(bodyGeometry, bottleMaterial);
  const shoulder = new THREE.Mesh(shoulderGeometry, bottleMaterial);
  shoulder.position.y = 1.34;
  const neck = new THREE.Mesh(neckGeometry, bottleMaterial);
  neck.position.y = 1.8;

  bottleGroup.add(body);
  bottleGroup.add(shoulder);
  bottleGroup.add(neck);

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
  let pendingIndex = null;
  setDescription(0);

  const getSectionProgress = () => {
    if (!scrollSection) return 0;
    const rect = scrollSection.getBoundingClientRect();
    const totalScrollable = Math.max(1, rect.height - window.innerHeight);
    const progress = Math.min(Math.max(-rect.top / totalScrollable, 0), 1);
    return progress;
  };

  const clock = new THREE.Clock();

  const animate = () => {
    const elapsed = clock.getElapsedTime();
    const globalProgress = getSectionProgress();

    const total = bottleDescriptions.length;
    const scaled = globalProgress * total;
    const rawIndex = Math.min(total - 1, Math.floor(scaled));
    const localProgress = Math.min(0.9999, scaled - rawIndex);

    const blurStart = 0.8;
    const blurEnd = 1.0;
    let blurStrength = 0;

    if (localProgress >= blurStart) {
      const t = (localProgress - blurStart) / (blurEnd - blurStart);
      blurStrength = Math.min(Math.max(t, 0), 1);
    }

    if (descriptionWrap) {
      descriptionWrap.style.filter = `blur(${(blurStrength * 12).toFixed(2)}px)`;
      descriptionWrap.style.opacity = `${(1 - blurStrength * 0.55).toFixed(3)}`;
    }

    if (rawIndex !== currentIndex && pendingIndex !== rawIndex) {
      pendingIndex = rawIndex;
    }

    if (pendingIndex !== null && localProgress >= 0.86) {
      currentIndex = pendingIndex;
      pendingIndex = null;
      setDescription(currentIndex);
      bottleMaterial.color.setHex(palette[currentIndex % palette.length]);
    }

    bottleGroup.rotation.y = globalProgress * Math.PI * 2.15;
    bottleGroup.position.y = Math.sin(elapsed * 1.2) * 0.03 + (blurStrength > 0.05 ? (1 - blurStrength) * 0.08 : 0);

    renderer.render(scene, camera);
    requestAnimationFrame(animate);
  };

  animate();
})();
