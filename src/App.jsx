import { Routes, Route } from "react-router-dom"
import Layout from './Layout.jsx'
import Homepage from './Homepage.jsx'
import Afiliacion from './Afiliacion.jsx'
import Ranking from './Ranking.jsx'
import Organigrama from './Organigrama.jsx'
import Equipos from './Equipos-y-Ligas.jsx'
import Reglamento from './Reglamento.jsx'


function App() {
  return (

      <Routes>
        <Route element={<Layout/>}>
        <Route path="/" element={<Homepage />} />
        <Route path="/afiliacion" element={<Afiliacion />} />
        <Route path="/ranking" element={<Ranking />} />
        <Route path="/organigrama" element={<Organigrama />} />
        <Route path="/equipos-y-ligas" element={<Equipos />} />
        <Route path="/reglamento" element={<Reglamento />} />
        </Route>
      </Routes>


  );
}


export default App
