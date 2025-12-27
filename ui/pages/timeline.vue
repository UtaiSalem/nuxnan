<template>
  <section>
    <div class="gap no-gap">
      <div class="profile-pic">
        <img src="/images/resources/profile-baner.jpg" alt="">
        <div class="user-dp">
          <figure>
            <img src="/images/resources/user3.jpg" alt="">
            <span class="status greenbg"></span>
          </figure>
          <h5>Monica Light</h5>
          <ul>
            <li><NuxtLink class="redbg" to="/about" title="Profile"><i class="lni lni-user"></i></NuxtLink></li>
            <li><NuxtLink class="bluebg" to="/settings" title="Edit Profile"><i class="lni lni-pencil"></i></NuxtLink></li>
            <li><a class="yellowbg" href="#" title="Follow"><i class="lni lni-star"></i></a></li>
          </ul>
        </div>
      </div>
    </div>
  </section>

  <section>
    <div class="gap no-gap">
      <div class="profile-info">
        <ul>
          <li title="followers"><i class="lni lni-star"></i><ins>1.5k</ins></li>
          <li title="following"><i class="lni lni-users"></i><ins>223</ins></li>
          <li title="Likes"><i class="lni lni-heart"></i><ins>665</ins></li>
          <li title="Posts"><i class="lni lni-postcard"></i><ins>103</ins></li>
        </ul>
      </div>
    </div>
  </section>

  <section>
    <div class="gap no-top overlap">
      <div class="page-caro">
        <div class="link-item">
          <NuxtLink to="/about" title="">
            <i class="lni lni-user"></i>
            <p>About</p>
          </NuxtLink>
        </div>
        <div class="link-item">
          <NuxtLink to="/timeline" title="">
            <i class="lni lni-keyboard"></i>
            <p>Timeline</p>
          </NuxtLink>
        </div>
        <div class="link-item">
          <NuxtLink to="/followers" title="">
            <i class="lni lni-emoji-smile"></i>
            <p>Followers</p>
          </NuxtLink>
        </div>
        <div class="link-item">
          <NuxtLink to="/groups" title="">
            <i class="lni lni-network"></i>
            <p>Groups</p>
          </NuxtLink>
        </div>
        <div class="link-item">
          <NuxtLink to="/photos" title="">
            <i class="lni lni-gallery"></i>
            <p>Photos</p>
          </NuxtLink>
        </div>
        <div class="link-item">
          <NuxtLink to="/videos" title="">
            <i class="lni lni-play"></i>
            <p>Videos</p>
          </NuxtLink>
        </div>
      </div>
    </div>
  </section>

  <section>
    <div class="gap no-gap">
      <div class="container">
        <div class="row">
          <div class="col-lg-12">
            <h5 class="main-title">Share it</h5>
            <ul class="social-share">
              <li><a class="facebook" href="#" title=""><i class="lni lni-facebook"></i></a></li>
              <li><a class="twitter" href="#" title=""><i class="lni lni-twitter"></i></a></li>
              <li><a class="youtube" href="#" title=""><i class="lni lni-youtube"></i></a></li>
              <li><a class="pinterest" href="#" title=""><i class="lni lni-pinterest"></i></a></li>
              <li><a class="instagram" href="#" title=""><i class="lni lni-instagram"></i></a></li>
              <li><a class="dribble" href="#" title=""><i class="lni lni-dribbble"></i></a></li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section>
    <div class="gap">
      <div class="container">
        <div class="row">
          <div class="col-12">
            <div class="user-post">
              <figure><img src="/images/resources/user3.jpg" alt=""></figure>
              <div class="user-name">
                <h5>Monica Light</h5>
                <span>1 weeks ago</span>
              </div>
              <div class="post-meta">
                <p>
                  sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat. David !
                </p>
                <img src="/images/resources/img-1.jpg" alt="">
              </div>
              <div class="more ico-hover">
                <i class="lni lni-more"></i>
                <div class="more-options">
                  <ul>
                    <li><i class="lni lni-trash"></i> Delete Post</li>
                    <li><i class="lni lni-ban"></i> Hide Post</li>
                    <li><i class="lni lni-pencil-alt"></i> Edit Post</li>
                  </ul>
                </div>
              </div>
              <div class="user-stat">
                <div class="emoji-state">
                  <img src="/images/svg/thumb.png" alt="">
                  <img src="/images/svg/heart.png" alt="">
                  <img src="/images/svg/smile.png" alt="">
                  <img src="/images/svg/weep.png" alt="">
                  <p>10</p>
                </div>
                <div class="coment-state">
                  <span><i class="lni lni-comments"></i>24 comments</span>
                  <span><i class="lni lni-share"></i>56 shares</span>
                </div>
              </div>
              <div class="stat-tools">
                <div class="box">
                  <div class="Like"><a class="Like__link"><i class="lni lni-heart"></i> Like</a>
                    <div class="Emojis">
                      <div class="Emoji Emoji--like">
                        <div class="icon icon--like"></div>
                      </div>
                      <div class="Emoji Emoji--love">
                        <div class="icon icon--heart"></div>
                      </div>
                      <div class="Emoji Emoji--haha">
                        <div class="icon icon--haha"></div>
                      </div>
                      <div class="Emoji Emoji--wow">
                        <div class="icon icon--wow"></div>
                      </div>
                      <div class="Emoji Emoji--sad">
                        <div class="icon icon--sad"></div>
                      </div>
                      <div class="Emoji Emoji--angry">
                        <div class="icon icon--angry"></div>
                      </div>
                    </div>
                  </div>
                </div>
                <a class="comment-to" href="#" title=""><i class="lni lni-comments"></i> comment</a>
                <a class="share-to" href="#" title=""><i class="lni lni-share-alt"></i> share</a>
                <div class="new-comment">
                  <form method="post">
                    <input type="text" placeholder="write comment">
                    <button type="submit"><i class="lni lni-pointer"></i></button>
                  </form>
                </div>
              </div>
            </div>
            
            <div class="loadmore">
              <div class="loader">
                <div class="line"></div>
                <div class="line"></div>
                <div class="line"></div>
                <div class="line"></div>
              </div>
              <span>Loading...</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

