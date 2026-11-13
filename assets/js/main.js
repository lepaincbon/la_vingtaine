(() => {
  const loader = document.getElementById('loader');

  window.addEventListener('load', () => {
    if (loader) {
      loader.classList.add('is-hidden');
      setTimeout(() => loader.remove(), 700);
    }
  });

  const canvas = document.getElementById('bottle-scene');
  if (!canvas || typeof THREE === 'undefined') return;

  const scene = new THREE.Scene();
  scene.background = new THREE.Color(0xf7f1e8);

  const renderer = new THREE.WebGLRenderer({
    canvas,
    antialias: true,
    alpha: true
  });
  renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));

  const camera = new THREE.PerspectiveCamera(40, 1, 0.1, 100);
  camera.position.set(0, 1.8, 9);
  scene.add(camera);

  const ambientLight = new THREE.AmbientLight(0xffffff, 1.1);
  scene.add(ambientLight);

  const keyLight = new THREE.DirectionalLight(0xffffff, 1.2);
  keyLight.position.set(4, 8, 6);
  scene.add(keyLight);

  const fillLight = new THREE.DirectionalLight(0xdcc3a5, 0.7);
  fillLight.position.set(-4, 3, -2);
  scene.add(fillLight);

  const bottlesGroup = new THREE.Group();
  scene.add(bottlesGroup);

  const bottleCount = 8;
  const radius = 4.2;
  const bottleGeometry = new THREE.CylinderGeometry(0.35, 0.45, 2.2, 32, 1, false);

  const palette = [
    0x6b3f28, // brown
    0x9c6b4f,
    0xc8ad8d, // beige
    0xb58e6b,
    0x7a4f35
  ];

  for (let i = 0; i < bottleCount; i++) {
    const angle = (i / bottleCount) * Math.PI * 2;
    const color = palette[i % palette.length];

    const bodyMaterial = new THREE.MeshStandardMaterial({
      color,
      roughness: 0.35,
      metalness: 0.15
    });

    const bottle = new THREE.Mesh(bottleGeometry, bodyMaterial);

    const neck = new THREE.Mesh(
      new THREE.CylinderGeometry(0.18, 0.22, 0.5, 24),
      bodyMaterial
    );
    neck.position.y = 1.2;
    bottle.add(neck);

    bottle.position.set(
      Math.cos(angle) * radius,
      0,
      Math.sin(angle) * radius
    );
    bottle.lookAt(0, 0.6, 0);
    bottle.userData = { angleOffset: angle };

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

  const clock = new THREE.Clock();

  const setSize = () => {
    const parent = canvas.parentElement;
    const width = parent ? parent.clientWidth : window.innerWidth;
    const height = parent ? parent.clientHeight : 500;

    camera.aspect = width / height;
    camera.updateProjectionMatrix();
    renderer.setSize(width, height, false);
  };

  setSize();
  window.addEventListener('resize', setSize);

  const animate = () => {
    const elapsed = clock.getElapsedTime();

    bottlesGroup.children.forEach((bottle, index) => {
      const base = bottle.userData.angleOffset || 0;
      const speed = 0.18;
      const angle = base + elapsed * speed;

      bottle.position.x = Math.cos(angle) * radius;
      bottle.position.z = Math.sin(angle) * radius;
      bottle.position.y = Math.sin(elapsed * 1.6 + index * 0.5) * 0.08;

      bottle.lookAt(0, 0.5, 0);
    });

    bottlesGroup.rotation.y = Math.sin(elapsed * 0.2) * 0.08;

    renderer.render(scene, camera);
    requestAnimationFrame(animate);
  };

  animate();
})();
