import { storeToRefs } from 'pinia'
import { useGamificationStore } from '~/stores/gamification'

export const useGamificationProgress = () => {
  const store = useGamificationStore()
  const { progress, dashboard, quests, isLoading } = storeToRefs(store)

  const fetchAll = async (force = false) => {
    return Promise.all([
      store.fetchProgress(force),
      store.fetchDashboard(force),
      store.fetchQuests(force),
    ])
  }

  const claimQuest = async (questId: number) => {
    return store.claimQuestReward(questId)
  }

  return {
    progress,
    dashboard,
    quests,
    isLoading,
    fetchAll,
    claimQuest,
  }
}
