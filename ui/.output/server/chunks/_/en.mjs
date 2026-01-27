var common = {
	welcome: "Welcome",
	home: "Home",
	search: "Search",
	settings: "Settings",
	profile: "Profile",
	logout: "Logout",
	login: "Login",
	register: "Register",
	submit: "Submit",
	cancel: "Cancel",
	save: "Save",
	edit: "Edit",
	"delete": "Delete",
	confirm: "Confirm",
	close: "Close",
	back: "Back",
	next: "Next",
	previous: "Previous",
	loading: "Loading...",
	noData: "No Data",
	error: "Error",
	success: "Success"
};
var nav = {
	dashboard: "Dashboard",
	newsfeed: "Newsfeed",
	timeline: "Timeline",
	friends: "Friends",
	messages: "Messages",
	notifications: "Notifications",
	schools: "Schools",
	courses: "Courses",
	lessons: "Lessons",
	earn: "Earn",
	points: "Points",
	wallet: "Wallet",
	coupons: "Coupons",
	rewards: "Rewards",
	achievements: "Achievements",
	settings: "Settings",
	community: "Community",
	about: "About",
	contact: "Contact",
	help: "Help",
	privacy: "Privacy",
	terms: "Terms of Service",
	leaderboard: "Leaderboard",
	topLearners: "Top Learners"
};
var auth = {
	loginTitle: "Login",
	registerTitle: "Register",
	email: "Email",
	username: "Username",
	password: "Password",
	confirmPassword: "Confirm Password",
	rememberMe: "Remember Me",
	forgotPassword: "Forgot Password?",
	noAccount: "Don't have an account?",
	haveAccount: "Already have an account?",
	signInWithGoogle: "Sign in with Google",
	signInWithFacebook: "Sign in with Facebook",
	or: "or",
	loginField: "Email / Phone / Username / Member ID",
	loginFieldPlaceholder: "Enter email, phone, username or member ID",
	firstName: "First Name",
	lastName: "Last Name",
	phone: "Phone Number",
	agreeToTerms: "I agree to the",
	termsAndConditions: "Terms and Conditions",
	privacyPolicy: "Privacy Policy",
	loginSuccess: "Login Successful",
	registerSuccess: "Registration Successful",
	logoutSuccess: "Logout Successful",
	referralCode: "Referral Code",
	referralCodeRequired: "Referral Code Required",
	referralCodePlaceholder: "Enter Referral Code (8 digits)",
	enterReferralCode: "Please enter your referrer's code to continue",
	noReferrer: "I Don't Have a Referrer",
	showPassword: "Show Password"
};
var validation = {
	required: "Please enter {field}",
	email: "Please enter a valid email",
	minLength: "{field} must be at least {min} characters",
	maxLength: "{field} must not exceed {max} characters",
	passwordMatch: "Passwords do not match",
	invalidCredentials: "Invalid credentials. Please check your login information and password",
	invalidLoginField: "Please enter email, phone number, username or member ID",
	userExists: "User already exists",
	invalidPhone: "Please enter a valid phone number"
};
var errors = {
	general: "An error occurred. Please try again",
	network: "Network connection failed",
	unauthorized: "You are not authorized",
	notFound: "Not found",
	serverError: "Server error occurred",
	timeout: "Connection timeout"
};
var messages = {
	saveSuccess: "Saved successfully",
	deleteSuccess: "Deleted successfully",
	updateSuccess: "Updated successfully",
	confirmDelete: "Are you sure you want to delete?"
};
var user = {
	guest: "Guest",
	online: "Online",
	offline: "Offline",
	lastSeen: "Last seen",
	followers: "Followers",
	following: "Following",
	posts: "Posts",
	photos: "Photos",
	videos: "Videos",
	friends: "Friends",
	visits: "Visits"
};
var post = {
	whatsOnYourMind: "What's on your mind?",
	share: "Share",
	like: "Like",
	comment: "Comment",
	comments: "Comments",
	likes: "Likes",
	shares: "Shares",
	writeComment: "Write a comment...",
	viewMore: "View More",
	viewLess: "View Less"
};
var language = {
	th: "ภาษาไทย",
	en: "English",
	selectLanguage: "Select Language"
};
const en = {
	common: common,
	nav: nav,
	auth: auth,
	validation: validation,
	errors: errors,
	messages: messages,
	user: user,
	post: post,
	language: language
};

export { auth, common, en as default, errors, language, messages, nav, post, user, validation };
//# sourceMappingURL=en.mjs.map
