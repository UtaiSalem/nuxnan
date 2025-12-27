export const useUserProfile = () => {
  const profile = ref({
    name: 'Deen Doughouz',
    username: '@deendoughouz',
    bio: 'Digital Creator & Designer | Travel Enthusiast 🌍',
    avatar: 'https://i.pravatar.cc/200?img=5',
    coverPhoto: 'https://picsum.photos/1200/400?random=10',
    location: 'San Francisco, CA',
    work: 'Senior Designer at Tech Corp',
    education: 'Bachelor of Design, University of Arts',
    website: 'https://deendoughouz.com',
    stats: {
      posts: 234,
      followers: 1234,
      following: 567
    },
    photos: [
      'https://picsum.photos/400/400?random=11',
      'https://picsum.photos/400/400?random=12',
      'https://picsum.photos/400/400?random=13',
      'https://picsum.photos/400/400?random=14',
      'https://picsum.photos/400/400?random=15',
      'https://picsum.photos/400/400?random=16'
    ],
    friends: [
      {
        id: 1,
        name: 'Sarah Anderson',
        avatar: 'https://i.pravatar.cc/150?img=1',
        mutualFriends: 45
      },
      {
        id: 2,
        name: 'John Smith',
        avatar: 'https://i.pravatar.cc/150?img=2',
        mutualFriends: 23
      },
      {
        id: 3,
        name: 'Emily Chen',
        avatar: 'https://i.pravatar.cc/150?img=3',
        mutualFriends: 67
      }
    ]
  })

  const userPosts = ref([
    {
      id: 101,
      author: {
        name: profile.value.name,
        avatar: profile.value.avatar,
        username: profile.value.username
      },
      content: 'Just launched my new portfolio! Check it out 🎨',
      images: ['https://picsum.photos/800/600?random=20'],
      timestamp: '1 hour ago',
      likes: 89,
      comments: 12,
      shares: 4,
      isLiked: false
    },
    {
      id: 102,
      author: {
        name: profile.value.name,
        avatar: profile.value.avatar,
        username: profile.value.username
      },
      content: 'Working on something exciting! Stay tuned 🚀',
      images: [],
      timestamp: '1 day ago',
      likes: 156,
      comments: 23,
      shares: 7,
      isLiked: true
    }
  ])

  return {
    profile,
    userPosts
  }
}
