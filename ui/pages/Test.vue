<template>
    <div>
        <div class="container bg-slate-200 ">
            <div class="flex flex-col justify-center items-center w-screen h-screen">
                <div class=" max-w-6xl ">
                    <div class="bg-white shadow-lg rounded-lg m-6 p-6 space-y-4">
                        <div class="card-header underline">
                            <Link href="/newsfeed">Laravel Vue JS File Upload Demo</Link>
                        </div>
                        <div class="card-body">
                            <div v-if="form.success != ''" class="alert alert-success">
                                {{ form.success }}
                            </div>
                            <form @submit="formSubmit" enctype="multipart/form-data">
                                <input type="file" class="form-control" @change="onChange">
                                <button type="submit" class="bg-green-500 text-white p-2 rounded-lg">Upload</button>
                            </form>
                        </div>
                    </div>

                    <div class="bg-white shadow-lg rounded-lg m-6 p-6 space-y-4">
                        <div class="w-40 h-40">
                            <img :src="form.img" alt="">
                        </div>
                        <div>                           
                                {{ form.name }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';
import { Link } from '@inertiajs/vue3'
// const name = ref('');
// const file = ref(null);
// const img = ref(null);
// const success = ref('');

const form = ref({
    name:'', 
    cover:null, 
    img:'', 
    success:'' 
});

function onChange(event) { 
    form.value.cover = event.target.files[0]; 
    form.value.img = URL.createObjectURL(event.target.files[0]);
}

function formSubmit(e) {
    e.preventDefault();
    // let existingObj = this;
    const config = { headers: { 'content-type': 'multipart/form-data' } }

    let data = new FormData();
    data.append('cover', form.value.cover);

    axios.post('test/upload', data, config)
        .then(function (res) {
            form.value.success = res.data.success;
            form.value.name = res.data.cover
        })
        .catch(function (err) {
            // Handle error silently
        });
}
        
</script>

