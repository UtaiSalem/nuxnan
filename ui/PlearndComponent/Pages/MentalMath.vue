<script setup>
import { ref, onMounted } from 'vue';

const randomNumbers = ref([0,0,0,0]);
const tempResult = ref(0);
const colection1 = [1,2,3,4];
const isStart = ref(false);
const speed = ref(4);
const loadingNumber = ref(5);
let intervalId;
// Function to generate a random number between min and max, excluding 0
const getRandomNumber = (min, max) => {
  let number = 0;
  while (number === 0) {
    number = Math.floor(Math.random() * (max - min + 1)) + min;
  }
  return number;
};

// Function to update randomNumbers array
const updateRandomNumbers = () => {
  let num1 = colection1[Math.floor(Math.random() * 4)];
  let num2 = colection1[Math.floor(Math.random() * 4)];

  let sum12 = num1+num2;

  if (sum12>4 && num1>num2){
        num2 = -num2;
  }else if(sum12>4 && num1<=num2){
        let tempNum = num1;
        num1 = num2;
        num2 = -tempNum;
  }

  sum12 = num1+num2;

  let num3 = colection1[Math.floor(Math.random() * 4)];

  let sum123 = sum12 + num3;

  if (sum123 > 4 && sum12 >= num3) {
    num3 = -num3;
  }else if(sum123>4 && sum12<num3){
    num3 -= sum12;
  }

  sum123 = sum12+num3

  let num4 = colection1[Math.floor(Math.random() * 4)];

  let sum1234 = sum123+num4;

  if (sum1234 > 4 && sum123 >= num4) {
    num4 = -num4;
  }else if(sum1234 > 4 && sum123 < num4){
    num4 -=sum123;
  }

  sum1234 = sum123+num4;

  // Check conditions
  if ( 0 <= sum1234 < 4) {
    randomNumbers.value = [num1, num2, num3 , num4];
    tempResult.value = sum1234;
  } else {
    // Retry if conditions are not met
    updateRandomNumbers();
  }
};

// Function to update randomNumbers array every 2 seconds
const startTimer = () => {
    isStart.value = true;
    intervalId = setInterval(updateRandomNumbers, speed.value * 1000);
};

const stopInterval = () => {
    clearInterval(intervalId);
    isStart.value = false;
};
// Lifecycle hook to start the timer when the component is mounted
// onMounted(() => {
//   startTimer();
// });

// Reactive variables
const showTimer = ref(false);
const secondsElapsed = ref(5);
const loading = () => {
    // Function to start the timer after a delay of 5 seconds
    showTimer.value = true;
    startTimer();
    // setTimeout(() => {
        // Update secondsElapsed every second
    const intervalId = setInterval(() => {
        secondsElapsed.value -= 1;
    }, 1000);

    // Stop the interval after 5 seconds
    setTimeout(() => {
        clearInterval(intervalId);
        showTimer.value = false;
    }, loadingNumber.value*1000);
    // }, loadingNumber.value*1000);
    // Delay for 5 seconds before starting the timer

}
</script>

<template>
    <div>

        <div class="w-full h-screen max-h-fit flex flex-col items-center justify-between bg-green-200">

            <div class="bg-green-200 text-end md:flex ">
                <button v-if="isStart" @click.prevent="stopInterval" class="plearnd-btn-primary w-32 m-6">Stop</button>
                <button v-else @click.prevent="loading" class="plearnd-btn-primary w-24 m-6">Start</button>
                <!-- <div class="flex items-center">
                    <input type="number" v-model="speed" class="w-16 rounded-lg" min="1" max="10">
                    <span>ความเร็ว(วินาที)</span>
                </div> -->
            </div>

            <div>
                <div v-if="showTimer" class="plearnd-card w-56 h-24 flex flex-col items-center justify-center text-7xl my-2 font-bold">
                    {{ secondsElapsed }}
                </div>
                <div v-else v-for="(item, index) in randomNumbers" :key="index" class="plearnd-card w-56 h-24 flex flex-col items-center justify-center text-7xl my-2 font-bold">
                    {{ item }}
                </div>
            </div>


            <div class="text-gray-400 w-full text-end px-4">
                {{ tempResult }}
            </div>

        </div>

    </div>
</template>


