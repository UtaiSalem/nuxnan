/**
 * Mock data for testing Visit Feed timeline
 * ข้อมูลจำลองสำหรับทดสอบฟีดไทม์ไลน์การเยี่ยมบ้าน
 */

export const mockStudents = [
  { id: 1, first_name: 'สมชาย', last_name: 'ใจดี', classroom: 'ม.1/1' },
  { id: 2, first_name: 'สมหญิง', last_name: 'มานะ', classroom: 'ม.1/2' },
  { id: 3, first_name: 'วิชัย', last_name: 'เรืองแสง', classroom: 'ม.2/1' },
  { id: 4, first_name: 'ปราณี', last_name: 'สุขสันต์', classroom: 'ม.2/2' },
  { id: 5, first_name: 'อนุชา', last_name: 'ศรีสุข', classroom: 'ม.3/1' },
  { id: 6, first_name: 'วิภา', last_name: 'แก้วใส', classroom: 'ม.3/2' },
  { id: 7, first_name: 'ธนากร', last_name: 'วงศ์ดี', classroom: 'ม.1/3' },
  { id: 8, first_name: 'มาลี', last_name: 'ดอกไม้', classroom: 'ม.2/3' }
]

export const mockZones = [
  { id: 1, name: 'โซนกลาง', color: '#3B82F6' },
  { id: 2, name: 'โซนเหนือ', color: '#10B981' },
  { id: 3, name: 'โซนใต้', color: '#F59E0B' },
  { id: 4, name: 'โซนตะวันออก', color: '#8B5CF6' },
  { id: 5, name: 'โซนตะวันตก', color: '#EC4899' }
]

export const mockTeachers = [
  'ครูสมศักดิ์ แสงจันทร์',
  'ครูวิมล สุขใจ',
  'ครูประพันธ์ ปัญญา',
  'ครูสุดา กิจดี',
  'ครูนิพนธ์ เรืองศรี'
]

const getRandomDate = (daysAgo) => {
  const date = new Date()
  date.setDate(date.getDate() - daysAgo)
  return date.toISOString()
}

const getRandomElement = (arr) => arr[Math.floor(Math.random() * arr.length)]

const visitSummaries = [
  'เยี่ยมบ้านนักเรียนและพูดคุยกับผู้ปกครองเกี่ยวกับพัฒนาการทางการเรียน นักเรียนมีความตั้งใจเรียนดี มีการทำการบ้านสม่ำเสมอ ผู้ปกครองให้ความร่วมมือเป็นอย่างดี',
  'พบนักเรียนที่บ้าน สังเกตว่ามีสภาพแวดล้อมที่เอื้อต่อการเรียนรู้ พูดคุยกับผู้ปกครองเรื่องการดูแลสุขภาพและโภชนาการของนักเรียน',
  'ติดตามผลการเรียนของนักเรียนที่บ้าน พบว่านักเรียนมีปัญหาในการเข้าใจเนื้อหาบางวิชา แนะนำให้ผู้ปกครองช่วยติดตามการทำการบ้านอย่างใกล้ชิด',
  'เยี่ยมบ้านเพื่อส่งเสริมความสัมพันธ์ระหว่างบ้านและโรงเรียน นักเรียนมีพัฒนาการที่ดีขึ้น ผู้ปกครองมีความพึงพอใจกับการเรียนการสอน',
  'ประเมินสภาพความเป็นอยู่ของนักเรียน พบว่าครอบครัวมีความอบอุ่น นักเรียนได้รับการดูแลเป็นอย่างดี มีพื้นที่สำหรับทำการบ้าน',
  'พูดคุยกับผู้ปกครองเกี่ยวกับแผนการศึกษาในอนาคตของนักเรียน ผู้ปกครองต้องการให้นักเรียนเรียนต่อในระดับที่สูงขึ้น',
  'ติดตามพฤติกรรมนักเรียนที่บ้าน พบว่ามีการปรับตัวดีขึ้นจากครั้งที่แล้ว มีความรับผิดชอบมากขึ้น ช่วยเหลือผู้ปกครองทำงานบ้าน',
  'เยี่ยมบ้านเนื่องจากนักเรียนขาดเรียนบ่อย พบว่ามีปัญหาสุขภาพเล็กน้อย ได้หารือกับผู้ปกครองแล้วจะติดตามอาการอย่างใกล้ชิด'
]

const visitRisks = [
  ['นักเรียนขาดแรงจูงใจในการเรียน', 'สิ่งแวดล้อมมีเสียงรบกวนจากทีวี'],
  ['ผู้ปกครองยุ่งกับงานไม่ค่อยมีเวลาดูแล', 'นักเรียนชอบเล่นเกมมากเกินไป'],
  ['ฐานะครอบครัวไม่ค่อยดี', 'นักเรียนต้องช่วยทำงานบ้านหลังเลิกเรียน'],
  null,
  ['พื้นที่ทำการบ้านมีแสงสว่างไม่เพียงพอ'],
  null
]

const visitRecommendations = [
  ['กำหนดเวลาเรียนที่บ้านให้ชัดเจน', 'ลดเวลาใช้อุปกรณ์อิเล็กทรอนิกส์', 'ให้กำลังใจและรางวัลเมื่อทำการบ้านสำเร็จ'],
  ['จัดหาพื้นที่เงียบสำหรับทำการบ้าน', 'ผู้ปกครองควรหาเวลาคุยกับลูกอย่างน้อยวันละ 30 นาที'],
  ['ประสานงานกับฝ่ายสวัสดิการนักเรียน', 'จัดหาทุนการศึกษาเพิ่มเติม'],
  ['ส่งเสริมให้นักเรียนอ่านหนังสือเพิ่มเติม', 'กำหนดเป้าหมายการเรียนที่ชัดเจน'],
  ['จัดหาโคมไฟเพิ่มเติมสำหรับทำการบ้าน', 'ปรับโต๊ะเรียนให้เหมาะสมกับส่วนสูง'],
  ['ให้คำปรึกษาด้านการศึกษาต่อ', 'แนะนำทางเลือกในการศึกษา']
]

const followUpActions = [
  ['ติดตามผลการเรียนในเดือนหน้า', 'ประสานงานกับครูประจำชั้น'],
  ['นัดพูดคุยกับผู้ปกครองอีกครั้งใน 2 สัปดาห์', 'จัดกิจกรรมเสริมทักษะ'],
  ['ติดต่อฝ่ายสวัสดิการภายใน 3 วัน', 'เยี่ยมบ้านซ้ำในเดือนหน้า'],
  ['ส่งใบงานเสริมไปให้', 'ติดตามความก้าวหน้าทุกสัปดาห์'],
  ['ประสานงานฝ่ายอาคารสถานที่', 'แจกอุปกรณ์การเรียนเพิ่มเติม'],
  null
]

const imageUrls = [
  'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?w=400',
  'https://images.unsplash.com/photo-1588072432836-e10032774350?w=400',
  'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=400',
  'https://images.unsplash.com/photo-1427504494785-3a9ca7044f45?w=400',
  'https://images.unsplash.com/photo-1509062522246-3755977927d7?w=400',
  'https://images.unsplash.com/photo-1516534775068-ba3e7458af70?w=400'
]

export const generateMockVisits = (count = 30) => {
  const statuses = ['completed', 'in-progress', 'pending', 'cancelled']
  const statusWeights = [0.6, 0.2, 0.15, 0.05] // Most are completed
  
  const getWeightedStatus = () => {
    const rand = Math.random()
    let cumulative = 0
    for (let i = 0; i < statuses.length; i++) {
      cumulative += statusWeights[i]
      if (rand < cumulative) return statuses[i]
    }
    return statuses[0]
  }

  const visits = []
  
  for (let i = 1; i <= count; i++) {
    const student = getRandomElement(mockStudents)
    const zone = getRandomElement(mockZones)
    const teacher = getRandomElement(mockTeachers)
    const status = getWeightedStatus()
    const daysAgo = Math.floor(Math.random() * 60) // Last 60 days
    const hasImages = Math.random() > 0.3 // 70% have images
    const imageCount = hasImages ? Math.floor(Math.random() * 5) + 1 : 0
    const summaryIndex = Math.floor(Math.random() * visitSummaries.length)
    const riskIndex = Math.floor(Math.random() * visitRisks.length)
    const recIndex = Math.floor(Math.random() * visitRecommendations.length)
    const followIndex = Math.floor(Math.random() * followUpActions.length)

    visits.push({
      id: i,
      student_id: student.id,
      student: student,
      zone_id: zone.id,
      zone: zone,
      teacher_name: teacher,
      visitor_name: teacher,
      visit_date: getRandomDate(daysAgo),
      visit_status: status,
      summary: visitSummaries[summaryIndex],
      notes: visitSummaries[summaryIndex],
      duration: status === 'completed' ? Math.floor(Math.random() * 90) + 30 : null,
      risks: visitRisks[riskIndex],
      recommendations: visitRecommendations[recIndex],
      follow_up_actions: followUpActions[followIndex],
      next_schedule: status === 'completed' && Math.random() > 0.5 
        ? getRandomDate(-30) // Schedule for 30 days from now
        : null,
      images: hasImages ? Array.from({ length: imageCount }, (_, idx) => ({
        id: `${i}-${idx}`,
        url: getRandomElement(imageUrls),
        caption: `ภาพกิจกรรมการเยี่ยมบ้าน ${idx + 1}`
      })) : [],
      created_at: getRandomDate(daysAgo),
      updated_at: getRandomDate(Math.max(0, daysAgo - 5))
    })
  }

  // Sort by date descending (newest first)
  return visits.sort((a, b) => new Date(b.visit_date) - new Date(a.visit_date))
}

// Generate default mock data
export const mockVisits = generateMockVisits(30)

// Export a function to get fresh data
export const getMockData = () => ({
  stats: {
    total_students: mockStudents.length,
    total_visits: mockVisits.length,
    visits_this_month: mockVisits.filter(v => {
      const date = new Date(v.visit_date)
      const now = new Date()
      return date.getMonth() === now.getMonth() && date.getFullYear() === now.getFullYear()
    }).length,
    pending_visits: mockVisits.filter(v => v.visit_status === 'pending').length,
    completed_visits: mockVisits.filter(v => v.visit_status === 'completed').length
  },
  allVisits: mockVisits,
  recentVisits: mockVisits.slice(0, 10),
  zones: mockZones,
  students: mockStudents
})
