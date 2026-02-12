<template>
    <div>
        <div v-for="post in $page.props.activities.data" :key="post.id" class="bg-white rounded-lg shadow-lg overflow-hidden my-4">
            
            <div class="border-b-2 flex items-center gap-3 px-6 py-2">
                <div class="avatar online">
                    <div class="w-10 rounded-full ring ring-primary ring-offset-base-100 ring-offset-2">
                        <img :src=" $page.props.profilePath + post.post.posted_by.avatar" />
                    </div>
                </div>
                <div>
                    <!-- USER STATUS TITLE -->
                    <p class="user-status-title">
                        <a class="font-bold text-gray-800" href="profile-timeline.html">{{ post.post.posted_by.name }}</a>
                        <span class="mx-4 text-gray-600 normal-case">{{ post.action }}</span>
                    </p>
                    <!-- /USER STATUS TITLE -->
                
                    <!-- USER STATUS TEXT -->
                    <p class="user-status-text text-gray-600 text-sm">{{ post.created_at }}</p>
                    <!-- /USER STATUS TEXT -->
                </div>
            </div>
            
            <div class="border-b-2 px-6 py-2">
                <p>{{ post.post.body }}</p>
            </div>
            
            <div class="border-b-2">
                <div class="flex justify-between items-center mx-4">
                    <div class="flex space-x-4 items-center">
                        <div class="avatar-group -space-x-2 m-2 items-center">
                            <div class="avatar z-[3]">
                                <div class="w-6 h-6 bg-blue-400 rounded-full pt-1 pl-[2px]">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" class="w-4 h-4">
                                        <path d="M7.493 18.75c-.425 0-.82-.236-.975-.632A7.48 7.48 0 016 15.375c0-1.75.599-3.358 1.602-4.634.151-.192.373-.309.6-.397.473-.183.89-.514 1.212-.924a9.042 9.042 0 012.861-2.4c.723-.384 1.35-.956 1.653-1.715a4.498 4.498 0 00.322-1.672V3a.75.75 0 01.75-.75 2.25 2.25 0 012.25 2.25c0 1.152-.26 2.243-.723 3.218-.266.558.107 1.282.725 1.282h3.126c1.026 0 1.945.694 2.054 1.715.045.422.068.85.068 1.285a11.95 11.95 0 01-2.649 7.521c-.388.482-.987.729-1.605.729H14.23c-.483 0-.964-.078-1.423-.23l-3.114-1.04a4.501 4.501 0 00-1.423-.23h-.777zM2.331 10.977a11.969 11.969 0 00-.831 4.398 12 12 0 00.52 3.507c.26.85 1.084 1.368 1.973 1.368H4.9c.445 0 .72-.498.523-.898a8.963 8.963 0 01-.924-3.977c0-1.708.476-3.305 1.302-4.666.245-.403-.028-.959-.5-.959H4.25c-.832 0-1.612.453-1.918 1.227z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="avatar z-[2]">
                                <div class="w-6 h-6 bg-red-600 rounded-full pt-1 pl-[4px]">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" class="w-4 h-4">
                                        <path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="avatar z-[1]">
                                <div class="w-6 h-6 bg-blue-600 rounded-full pt-1 pl-[6px]">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" class="w-4 h-4">
                                        <path d="M15.73 5.25h1.035A7.465 7.465 0 0118 9.375a7.465 7.465 0 01-1.235 4.125h-.148c-.806 0-1.534.446-2.031 1.08a9.04 9.04 0 01-2.861 2.4c-.723.384-1.35.956-1.653 1.715a4.498 4.498 0 00-.322 1.672V21a.75.75 0 01-.75.75 2.25 2.25 0 01-2.25-2.25c0-1.152.26-2.243.723-3.218C7.74 15.724 7.366 15 6.748 15H3.622c-1.026 0-1.945-.694-2.054-1.715A12.134 12.134 0 011.5 12c0-2.848.992-5.464 2.649-7.521.388-.482.987-.729 1.605-.729H9.77a4.5 4.5 0 011.423.23l3.114 1.04a4.5 4.5 0 001.423.23zM21.669 13.773c.536-1.362.831-2.845.831-4.398 0-1.22-.182-2.398-.52-3.507-.26-.85-1.084-1.368-1.973-1.368H19.1c-.445 0-.72.498-.523.898.591 1.2.924 2.55.924 3.977a8.959 8.959 0 01-1.302 4.666c-.245.403.028.959.5.959h1.053c.832 0 1.612-.453 1.918-1.227z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="avatar">
                            <div class="w-8 h-8 rounded-full py-1 px-2">
                                <span class="font-semibold text-sm">10+</span>
                            </div>
                            </div>
                        </div>
                        <div class="avatar-group -space-x-6 m-2">
                            <div class="avatar">
                                <div class="w-8">
                                    <img src="https://placeimg.com/192/192/people" />
                                </div>
                            </div>
                            <div class="avatar">
                                <div class="w-8">
                                    <img src="https://placeimg.com/192/192/people" />
                                </div>
                            </div>
                            <div class="avatar">
                                <div class="w-8">
                                    <img src="https://placeimg.com/192/192/people" />
                                </div>
                            </div>
                            <div class="avatar placeholder">
                                <div class="w-8 bg-neutral-focus text-neutral-content">
                                    <span class=" text-[10px]">+99</span>
                                </div>
                            </div>
                        </div>                 
                    </div>
                    <div class="flex space-x-4 items-center" >
                        <div><span class="font-semibold text-[12px]">15 Comments</span></div>
                        <div><span class="font-semibold text-[12px]">0 Share</span></div>
                    </div>
                </div>
            </div>
            
            <div class="flex w-full justify-between bg-gray-50">
                <div class="w-full text-center">
                    <a class="w-full flex justify-center items-center gap-2 py-4 rounded-lg hover:scale-110 font-semibold opacity-50 hover:opacity-80">
                        <svg class="post-option-icon icon-thumbs-up w-6 h-6" fill="black">
                            <use xlink:href="#svg-thumbs-up"></use>
                        </svg>
                        <span>Like</span>
                    </a>
                </div>
                <div class="w-full text-center">
                    <a class="w-full flex justify-center items-center gap-2 py-4 rounded-lg hover:scale-110 font-semibold opacity-50 hover:opacity-80">
                        <svg class="post-option-icon icon-thumbs-up w-6 h-6" fill="black">
                            <use xlink:href="#svg-comment"></use>
                        </svg>
                        <span>Comment</span>
                    </a>
                </div>
                <div class="w-full text-center">
                    <a class="w-full flex justify-center items-center gap-2 py-4 rounded-lg hover:scale-110 font-semibold opacity-50 hover:opacity-80">
                        <svg class="post-option-icon icon-thumbs-up w-6 h-6 " fill="black">
                            <use xlink:href="#svg-share"></use>
                        </svg>
                        <span>Share</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>

</script>
