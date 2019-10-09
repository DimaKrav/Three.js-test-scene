<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>My first three.js app</title>
    <style>
        body {
            margin: 0;
        }

        canvas {
            width: 100%;
            height: 100%
        }
    </style>
    <script type="module">

        import * as THREE from './build/three.module.js';
        import {FBXLoader} from './jsm/loaders/FBXLoader.js';
        import {GUI} from './jsm/libs/dat.gui.module.js';
        import {Sky} from './jsm/objects/Sky.js';
        import {OrbitControls} from './jsm/controls/OrbitControls.js';

        var controls, cubeCamera;
        var sky, sunSphere;
        var spotLight;

        //ініціалізація сцени
        var scene = new THREE.Scene();

        //ініціалізація камери
        var camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);

        //Початкове позиціонування камери на старті
        camera.position.z = 500;
        camera.position.y = 100;



        function initSky() {
            // Add Sky
            sky = new Sky();
            sky.scale.setScalar(450000);
            scene.add(sky);
            // Add Sun Helper
            sunSphere = new THREE.Mesh(
                new THREE.SphereBufferGeometry(20000, 16, 8),
                new THREE.MeshBasicMaterial({color: 0xffffff})
            );
            sunSphere.position.y = -700000;
            sunSphere.visible = true;
            scene.add(sunSphere);


            /// GUI
            var effectController = {
                turbidity: 10,
                rayleigh: 2,
                mieCoefficient: 0.005,
                mieDirectionalG: 0.8,
                luminance: 1,
                inclination: 0.49, // elevation / inclination
                azimuth: 0.25, // Facing front,
                sun: !true
            };
            var distance = 400000;

            // LIGHTS
            scene.add(new THREE.HemisphereLight(0x443333, 0x111122));
            spotLight = new THREE.SpotLight(0xffffbb, effectController.luminance);
            spotLight.position.multiplyScalar(50);
            scene.add(spotLight);
            spotLight.castShadow = true;
            spotLight.shadow.mapSize.width = 2048;
            spotLight.shadow.mapSize.height = 2048;
            spotLight.shadow.camera.near = 200;
            spotLight.shadow.camera.far = 1500;
            spotLight.shadow.camera.fov = 40;
            spotLight.shadow.bias = -0.01;




            function guiChanged() {
                //зміна інтенсивності світла
                spotLight.intensity = effectController.luminance;

                var uniforms = sky.material.uniforms;
                uniforms["turbidity"].value = effectController.turbidity;
                uniforms["rayleigh"].value = effectController.rayleigh;
                uniforms["mieCoefficient"].value = effectController.mieCoefficient;
                uniforms["mieDirectionalG"].value = effectController.mieDirectionalG;
                uniforms["luminance"].value = effectController.luminance;

                var theta = Math.PI * (effectController.inclination - 0.5);
                var phi = 2 * Math.PI * (effectController.azimuth - 0.5);
                sunSphere.position.x = distance * Math.cos(phi);
                sunSphere.position.y = distance * Math.sin(phi) * Math.sin(theta);
                sunSphere.position.z = distance * Math.sin(phi) * Math.cos(theta);
                sunSphere.visible = effectController.sun;
                uniforms["sunPosition"].value.copy(sunSphere.position);
                renderer.render(scene, camera);

                //зміна позиції світла
                spotLight.position.set(distance * Math.cos(phi), distance * Math.sin(phi) * Math.sin(theta), distance * Math.sin(phi) * Math.cos(theta));


            }

            //Управління сонцем
            var gui = new GUI();
            gui.add(effectController, "turbidity", 1.0, 20.0, 0.1).onChange(guiChanged);
            gui.add(effectController, "rayleigh", 0.0, 4, 0.001).onChange(guiChanged);
            gui.add(effectController, "mieCoefficient", 0.0, 0.1, 0.001).onChange(guiChanged);
            gui.add(effectController, "mieDirectionalG", 0.0, 1, 0.001).onChange(guiChanged);
            gui.add(effectController, "luminance", 0.0, 2).onChange(guiChanged);
            gui.add(effectController, "inclination", 0, 1, 0.0001).onChange(guiChanged);
            gui.add(effectController, "azimuth", 0, 1, 0.0001).onChange(guiChanged);
            gui.add(effectController, "sun").onChange(guiChanged);
            guiChanged();
        }


        // cube camera for environment map
        cubeCamera = new THREE.CubeCamera( 1, 1000, 512 );
        cubeCamera.renderTarget.texture.generateMipmaps = true;
        cubeCamera.renderTarget.texture.minFilter = THREE.LinearMipmapLinearFilter;
        cubeCamera.renderTarget.texture.mapping = THREE.CubeReflectionMapping;


        cubeCamera.position.set( 0, - 100, 0 );
        scene.add( cubeCamera );

        //Загрузчик текстур
        var loader = new THREE.TextureLoader();
        var normal = loader.load('models/textures/sniperRifle_normal2.jpg');
        var textures = loader.load('models/textures/sniperRifle_albedo2.jpg');
        var bumpMap = loader.load('models/textures/sniperRifle_roughness2.jpg');
        var metalnessMap = loader.load('models/textures/sniperRifle_metallic2.jpg');



        //Модель зброї
        var loader = new FBXLoader();
        loader.setResourcePath('models/textures/');


        loader.load('models/source/sniperTSR.fbx', function (object) {

            console.log(object);
            object.traverse(function (child) {
                if (child.isMesh) {
                    child.castShadow = true;
                    child.receiveShadow = true;
                    child.material.normalMap = normal;
                    child.material.map = textures;
                    child.material.aoMap = metalnessMap;
                    child.material.aoMapIntensity = 1;
                    child.material.reflectivity = 0.3;
                    child.material.refractionRatio = 0.4;
                    child.material.roughness = 1; // attenuates roughnessMap
                    child.material.metalness = 1; // attenuates metalnessMap
                    child.material.metalnessMap = child.material.roughnessMap = loader.load('models/textures/sniperRifle_metallic2.jpg');
                    child.material.envMap = cubeCamera.renderTarget.texture;

                }
            });
            scene.add(object);
        });






        //Ініціалізація рендера
        var renderer = new THREE.WebGLRenderer();
        renderer.setSize(window.innerWidth, window.innerHeight);

        //прослуховувач події на зміну розміру вікна
        window.addEventListener('resize', onWindowResize, false);


        //контейнер рендеру сцени
        document.body.appendChild(renderer.domElement);

        initSky();

        //Ініціалізація управління
        controls = new OrbitControls(camera, renderer.domElement);
        controls.target.set(0, 0, 0);
        controls.update();



        //Добавити в сцену допоміжну сітка
        var helper = new THREE.GridHelper(500, 100, 0x704c49, 0x2e2423);
        helper.position.y = -100;
        scene.add(helper);


        console.log(controls.target);


        //Функція запуску сцени
        function animate() {
            requestAnimationFrame(animate);

            renderer.render(scene, camera);
        }
        animate();



        //На зміну розміру вікна міняти розмір рендену
        function onWindowResize() {
            camera.aspect = window.innerWidth / window.innerHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(window.innerWidth, window.innerHeight);
        }

    </script>
</head>
<body>
<script src="js/three.min.js"></script>
</body>
</html>