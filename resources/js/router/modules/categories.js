/** When your routing table is too long, you can split it into small modules**/
import Layout from '@/layout';

const excelRoutes = {
  path: '/categories',
  component: Layout,
  redirect: '/categories/1',
  name: 'Categories',
  meta: {
    title: '分类管理',
    icon: 'excel',
    permissions: ['view menu excel'],
  },
  children: [
    {
      path: '/categories/?id=1',
      component: () => import('@/views/categories/index'),
      name: 'SelectExcel',
      meta: { title: '男频' },
    },
    {
      path: '/categories/?id=2',
      component: () => import('@/views/categories/index'),
      name: 'MergeHeader',
      meta: { title: '女频' },
    },
  ],
};

export default excelRoutes;
