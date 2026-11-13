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

  const ambientLight = new THREE.AmbientLight(0xffffff, 1.1);
  scene.add(ambientLight);

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

  const bottleCount = 10;
  const radius = 4.2;
  const bodyGeometry = new THREE.CylinderGeometry(0.35, 0.45, 2.2, 32, 1, false);
  const neckGeometry = new THREE.CylinderGeometry(0.18, 0.22, 0.5, 24);

  const palette = [
    0x6b3f28, // marron
    0xc8ad8d, // beige
    0xc9a34f, // doré
    0x7a4f35,
    0xb58e6b
  ];

  for (let i = 0; i < bottleCount; i++) {
    const angle = (i / bottleCount) * Math.PI * 2;
    const color = palette[i % palette.length];

    const bodyMaterial = new THREE.MeshStandardMaterial({
      color,
      roughness: 0.32,
      metalness: 0.18
    });

    const bottle = new THREE.Mesh(bodyGeometry, bodyMaterial);

    const neck = new THREE.Mesh(neckGeometry, bodyMaterial);
    neck.position.y = 1.2;
    bottle.add(neck);

    bottle.position.set(Math.cos(angle) * radius, 0, Math.sin(angle) * radius);
    bottle.lookAt(0, 0.6, 0);
    bottle.userData = { angleOffset: angle, bobOffset: i * 0.5 };

    bottlesGroup.add(bottle);
  }

  const floor = new THREE.Mesh(
    new THREE.CircleGeometry(8, 64),
    new THREE.MeshStandardMaterial({
      color: 0xffffff,
      roughness: 0.95,
      metalness: 0.02
    })
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

  const clock = new THREE.Clock();

  const animate = () => {
    const elapsed = clock.getElapsedTime();

    bottlesGroup.children.forEach((bottle) => {
      const base = bottle.userData.angleOffset || 0;
      const bobOffset = bottle.userData.bobOffset || 0;
      const speed = 0.22;
      const angle = base + elapsed * speed;

      bottle.position.x = Math.cos(angle) * radius;
      bottle.position.z = Math.sin(angle) * radius;
      bottle.position.y = Math.sin(elapsed * 1.5 + bobOffset) * 0.08;

      bottle.lookAt(0, 0.55, 0);
    });

    bottlesGroup.rotation.y = Math.sin(elapsed * 0.22) * 0.08;

    renderer.render(scene, camera);
    requestAnimationFrame(animate);
  };

  animate();
})();
